<?php

namespace App\Http\Controllers;

use App\Models\Summary;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    /*
     * DOCU: Displays stored summaries and marks unseen summaries as seen.
     * @returns {View} The summary page.
     */
    public function index()
    {
        Summary::where('seen', false)->update(['seen' => true]);
        $summaries = Summary::orderBy('recorded_at', 'desc')->get();
        return view('summary', compact('summaries'));
    }

    /*
     * DOCU: Returns stored summaries ordered by record time.
     * @returns {Collection} The summary records.
     */
    public function api()
    {
        return Summary::orderBy('recorded_at', 'desc')->get();
    }

    /*
     * DOCU: Deletes the summaries identified in the request.
     * @param {Request} request - The request containing summary IDs.
     * @returns {JsonResponse} The deletion result or validation error.
     */
    public function delete(Request $request)
    {
        $ids = $request->input('ids');
        if (!is_array($ids) || $ids === []) {
            return response()->json(['success' => false, 'message' => 'No IDs provided'], 422);
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (count($ids) !== count($request->input('ids')) || min($ids) < 1) {
            return response()->json(['success' => false, 'message' => 'Invalid summary IDs'], 422);
        }

        Summary::whereIn('id', $ids)->delete();

        return response()->json(['success' => true]);
    }
}
