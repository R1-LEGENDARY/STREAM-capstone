<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    /*
     * DOCU: Displays the notification history and marks unseen notifications as seen.
     * @returns {View} The notifications page.
     */
    public function index()
    {
        Notification::where('seen', false)->update(['seen' => true]);
        $notifications = Notification::whereIn('status', ['Safe', 'Warning', 'Critical'])
            ->whereRaw('HOUR(recorded_at) = 4 OR HOUR(recorded_at) = 13')
            ->orderBy('recorded_at', 'desc')->get();
        return view('notifications', compact('notifications'));
    }

    /*
     * DOCU: Deletes the selected notifications and returns to the notification page.
     * @param {Request} request - The request containing notification IDs.
     * @returns {RedirectResponse} The notifications page redirect.
     */
    public function destroy(Request $request)
    {
        if (!empty($request->ids) && is_array($request->ids)) {
            Notification::whereIn('id', $request->ids)->delete();
        }
        return redirect()->route('notifications.index');
    }

    /*
     * DOCU: Deletes all stored notifications.
     * @returns {RedirectResponse} The notifications page redirect.
     */
    public function clear()
    {
        Notification::truncate();
        return redirect()->route('notifications.index');
    }

    /*
     * DOCU: Returns the latest notifications for asynchronous dashboard requests.
     * @returns {Collection} The filtered notifications ordered by record time.
     */
    public function api()
    {
        return Notification::whereIn('status', ['Safe', 'Warning', 'Critical'])
            ->whereRaw('HOUR(recorded_at) = 4 OR HOUR(recorded_at) = 13')
            ->orderBy('recorded_at', 'desc')->get();
    }
}
