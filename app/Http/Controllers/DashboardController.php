<?php

namespace App\Http\Controllers;

use App\Models\Reading;
use App\Models\Summary;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /*
     * DOCU: Fetches all readings for the dashboard based on the selected range or filter.
     * @param {Request} request - The incoming HTTP request containing filter and date range parameters.
     * @returns {View} - The dashboard view with the resolved readings and metadata.
    * @throws {Throwable} - May surface database errors while retrieving readings.
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'today');
        $fromRaw = $request->query('from');
        $toRaw = $request->query('to');

        $fromDateTime = $this->parseDateTime($fromRaw);
        $toDateTime = $this->parseDateTime($toRaw);

        $query = Reading::query();

        if ($fromDateTime && $toDateTime) {
            if ($fromDateTime->greaterThan($toDateTime)) {
                [$fromDateTime, $toDateTime] = [$toDateTime, $fromDateTime];
            }

            $query->whereBetween('recorded_at', [
                $fromDateTime->copy()->startOfMinute(),
                $toDateTime->copy()->endOfMinute()
            ]);
            $readings = $query->orderBy('recorded_at', 'desc')->get()->reverse()->values();
        } else if ($filter === 'today') {
            $query->whereDate('recorded_at', now()->toDateString());
            $readings = $query->orderBy('recorded_at', 'desc')->take(100)->get()->reverse()->values();
        } elseif ($filter === '7days') {
            $query->where('recorded_at', '>=', now()->subDays(7)->startOfDay());
            $readings = $query->orderBy('recorded_at', 'desc')->get()->reverse()->values();
        } elseif ($filter === '30days') {
            $query->where('recorded_at', '>=', now()->subDays(30)->startOfDay());
            $readings = $query->orderBy('recorded_at', 'desc')->get()->reverse()->values();
        } else {
            $readings = $query->orderBy('recorded_at', 'desc')->take(100)->get()->reverse()->values();
        }

        $from = $fromDateTime?->toIso8601String();
        $to = $toDateTime?->toIso8601String();
        $fromDate = $fromDateTime?->toDateString();
        $toDate = $toDateTime?->toDateString();
        $fromTime = $fromDateTime?->format('H:i');
        $toTime = $toDateTime?->format('H:i');
        $rangeLabel = ($fromDateTime && $toDateTime)
            ? $fromDateTime->format('Y-m-d H:i') . ' to ' . $toDateTime->format('Y-m-d H:i')
            : null;

        return view('dashboard', compact(
            'readings',
            'filter',
            'from',
            'to',
            'fromDate',
            'toDate',
            'fromTime',
            'toTime',
            'rangeLabel'
        ));
    }

    /*
     * DOCU: Returns the latest readings for the dashboard and creates scheduled summary records when conditions match.
     * @param {Request} request - The request containing filter and range values used to build the reading set.
     * @returns {Collection} - The filtered reading collection used for dashboard refreshes.
    * @throws {Throwable} - May surface database errors while retrieving readings or summaries.
     */
    public function api(Request $request)
    {
        $filter = $request->query('filter', 'today');
        $fromDateTime = $this->parseDateTime($request->query('from'));
        $toDateTime = $this->parseDateTime($request->query('to'));
        $query = Reading::query();

        if ($fromDateTime && $toDateTime) {
            if ($fromDateTime->greaterThan($toDateTime)) {
                [$fromDateTime, $toDateTime] = [$toDateTime, $fromDateTime];
            }

            $query->whereBetween('recorded_at', [
                $fromDateTime->copy()->startOfMinute(),
                $toDateTime->copy()->endOfMinute()
            ]);
            $readings = $query->orderBy('recorded_at', 'desc')->get()->reverse()->values();
        } else if ($filter === 'today') {
            $query->whereDate('recorded_at', now()->toDateString());
            $readings = $query->orderBy('recorded_at', 'desc')->take(100)->get()->reverse()->values();
        } elseif ($filter === '7days') {
            $query->where('recorded_at', '>=', now()->subDays(7)->startOfDay());
            $readings = $query->orderBy('recorded_at', 'desc')->get()->reverse()->values();
        } elseif ($filter === '30days') {
            $query->where('recorded_at', '>=', now()->subDays(30)->startOfDay());
            $readings = $query->orderBy('recorded_at', 'desc')->get()->reverse()->values();
        } else {
            $readings = $query->orderBy('recorded_at', 'desc')->take(100)->get()->reverse()->values();
        }

        if ($readings->count()) {
            $latest = $readings->last();
            $serverNow = now();
            $serverHour = $serverNow->hour;
            $serverMinute = $serverNow->minute;
            $isLiveRequest = !$fromDateTime && !$toDateTime && $filter === 'today';
            $latestTs = \Carbon\Carbon::parse($latest->recorded_at ?? $latest->created_at);

            if ($isLiveRequest && ($serverHour === 4 || $serverHour === 13) && $serverMinute === 0 && $latestTs->greaterThanOrEqualTo($serverNow->copy()->subMinutes(5))) {
                $summaryExists = Summary::whereRaw('HOUR(recorded_at) = ?', [$serverHour])
                    ->whereRaw('MINUTE(recorded_at) = 0')
                    ->whereDate('recorded_at', $serverNow->toDateString())
                    ->exists();
                if (!$summaryExists) {
                    Summary::create([
                        'temperature' => $latest->temperature,
                        'ph' => $latest->ph,
                        'dissolved_oxygen' => $latest->dissolved_oxygen,
                        'recorded_at' => $serverNow,
                        'seen' => false,
                    ]);
                }
            }
        }

        return $readings;
    }

    /*
     * DOCU: Parses an incoming date-time string into a Carbon object when valid.
     * @param {string|null} value - A raw date or date-time string from the request.
     * @returns {Carbon|null} - A Carbon instance or null if the value is missing or invalid.
     */
    protected function parseDateTime(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
