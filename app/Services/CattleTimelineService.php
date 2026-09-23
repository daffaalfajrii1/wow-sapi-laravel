<?php

namespace App\Services;

use App\Models\Cattle;
use App\Support\DatePeriod;
use Illuminate\Support\Collection;

class CattleTimelineService
{
    public function build(Cattle $cattle, ?DatePeriod $period = null, bool $excludeFailed = false): Collection
    {
        $cattle->load([
            'weightRecords',
            'aiExaminations',
            'bcsRecords',
            'healthRecords',
            'vaccinationSchedules.vaccine',
            'vaccinationRecords.vaccine',
            'reproductionRecords',
            'feedRecords',
            'mortality',
        ]);

        $items = collect();

        foreach ($cattle->weightRecords as $row) {
            $items->push($this->item($row->measured_at, 'bobot', 'Bobot '.id_kg($row->weight_kg).' ('.$row->sourceLabel().')', $row));
        }
        foreach ($cattle->aiExaminations as $row) {
            if ($excludeFailed && $this->isFailedAi($row)) {
                continue;
            }
            $items->push($this->item($row->examined_at, 'ai', $row->timelineTitle(), $row));
        }
        foreach ($cattle->bcsRecords as $row) {
            $items->push($this->item($row->assessed_at, 'bcs', 'BCS '.number_format((float) $row->score, 1).' ('.$row->category.')', $row));
        }
        foreach ($cattle->healthRecords as $row) {
            $items->push($this->item($row->occurred_at, 'kesehatan', $row->title, $row));
        }
        foreach ($cattle->vaccinationSchedules as $row) {
            $items->push($this->item($row->scheduled_date, 'vaksin', 'Jadwal '.$row->vaccine?->name.' ('.$row->statusLabel().')', $row));
        }
        foreach ($cattle->vaccinationRecords as $row) {
            $items->push($this->item($row->administered_at, 'vaksin', 'Vaksin '.$row->vaccine?->name.' diberikan', $row));
        }
        foreach ($cattle->reproductionRecords as $row) {
            $items->push($this->item($row->event_date, 'reproduksi', $row->typeLabel(), $row));
        }
        foreach ($cattle->feedRecords as $row) {
            $items->push($this->item($row->fed_at, 'pakan', $row->feed_name, $row));
        }
        if ($cattle->mortality) {
            $items->push($this->item($cattle->mortality->died_at, 'kematian', 'Kematian dicatat', $cattle->mortality));
        }

        $items = $items->sortByDesc(fn ($i) => $i['at'])->values();

        if ($period && ($period->from || $period->to)) {
            $items = $items->filter(fn ($i) => $period->contains($i['at']))->values();
        }

        return $items;
    }

    public function report(Cattle $cattle, ?DatePeriod $period = null, bool $excludeFailed = false): array
    {
        $cattle->load(['farmer.user', 'breed', 'latestWeight', 'latestBcs', 'latestLumpy', 'mortality']);

        $weights = $cattle->weightRecords()->orderBy('measured_at');
        $ai = $cattle->aiExaminations()->latest('examined_at');
        $lumpy = $cattle->aiExaminations()->whereNotNull('lumpy_detected')->latest('examined_at');
        if ($excludeFailed) {
            $ai->whereNotIn('status', ['failed', 'error']);
            $lumpy->whereNotIn('status', ['failed', 'error']);
        }
        $bcs = $cattle->bcsRecords()->with('creator')->latest('assessed_at');
        $health = $cattle->healthRecords()->latest('occurred_at');
        $vaccinations = $cattle->vaccinationRecords()->with('vaccine')->latest('administered_at');
        $schedules = $cattle->vaccinationSchedules()->with('vaccine')->latest('scheduled_date');
        $reproduction = $cattle->reproductionRecords()->latest('event_date');
        $feeds = $cattle->feedRecords()->latest('fed_at');
        $feedCost = $cattle->feedRecords();

        if ($period) {
            $period->apply($weights, 'measured_at');
            $period->apply($ai, 'examined_at');
            $period->apply($lumpy, 'examined_at');
            $period->apply($bcs, 'assessed_at');
            $period->apply($health, 'occurred_at');
            $period->apply($vaccinations, 'administered_at');
            $period->apply($schedules, 'scheduled_date');
            $period->apply($reproduction, 'event_date');
            $period->apply($feeds, 'fed_at');
            $period->apply($feedCost, 'fed_at');
        }

        $mortality = $cattle->mortality;
        if ($mortality && $period && ! $period->contains($mortality->died_at)) {
            $mortality = null;
        }

        return [
            'cattle' => $cattle,
            'period' => $period,
            'timeline' => $this->build($cattle, $period, $excludeFailed),
            'weights' => $weights->get(),
            'ai' => $ai->get(),
            'lumpy' => $lumpy->get(),
            'bcs' => $bcs->get(),
            'health' => $health->get(),
            'vaccinations' => $vaccinations->get(),
            'schedules' => $schedules->get(),
            'reproduction' => $reproduction->get(),
            'feeds' => $feeds->get(),
            'feed_cost_total' => (float) $feedCost->sum('cost'),
            'mortality' => $mortality,
        ];
    }

    protected function item($at, string $kind, string $title, mixed $record): array
    {
        return [
            'at' => optional($at)?->toDateTimeString() ?? (string) $at,
            'kind' => $kind,
            'title' => $title,
            'record' => $record,
        ];
    }

    protected function isFailedAi(mixed $row): bool
    {
        $status = strtolower((string) ($row->status ?? ''));

        return in_array($status, ['failed', 'error'], true);
    }
}
