<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobOrder;
use App\Models\RepairProgressPhoto;
use App\Models\Attachment;
use App\Models\PhotoComment;
use Illuminate\Support\Facades\Auth;

class PhotoCommentController extends Controller
{
    /**
     * Fetch comments & replies for a specific photo (AJAX endpoint)
     */
    public function getPhotoComments(Request $request, string $photoType, int $photoId)
    {
        $modelClass = $photoType === 'attachment' 
            ? Attachment::class 
            : RepairProgressPhoto::class;

        $photo = $modelClass::findOrFail($photoId);

        $comments = PhotoComment::where('photo_type', $modelClass)
            ->where('photo_id', $photo->id)
            ->whereNull('parent_id')
            ->with(['replies' => function ($q) {
                $q->with('user')->orderBy('created_at', 'asc');
            }, 'user'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => PhotoComment::where('photo_type', $modelClass)->where('photo_id', $photo->id)->count(),
            'comments' => $comments,
        ]);
    }

    /**
     * Store comment or reply from Customer Portal (No login required, authenticated via job order token)
     */
    public function storeCustomerComment(Request $request, string $token)
    {
        $jobOrder = JobOrder::findByReference($token);

        if (!$jobOrder) {
            abort(404, 'Invalid tracking token or ticket number.');
        }

        $request->validate([
            'photo_type' => 'required|string|in:progress_photo,attachment',
            'photo_id' => 'required|integer',
            'comment' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:photo_comments,id',
            'author_name' => 'nullable|string|max:100',
        ]);

        $modelClass = $request->photo_type === 'attachment' 
            ? Attachment::class 
            : RepairProgressPhoto::class;

        $photo = $modelClass::findOrFail($request->photo_id);

        $authorName = $request->author_name 
            ?: ($jobOrder->customer?->name ? $jobOrder->customer->name . ' (Customer)' : 'Customer');

        $comment = PhotoComment::create([
            'job_order_id' => $jobOrder->id,
            'photo_type' => $modelClass,
            'photo_id' => $photo->id,
            'parent_id' => $request->parent_id,
            'user_id' => Auth::check() ? Auth::id() : null,
            'author_name' => Auth::check() ? Auth::user()->name . ' (' . ucfirst(Auth::user()->role ?? 'Staff') . ')' : $authorName,
            'author_type' => Auth::check() ? (Auth::user()->role === 'technician' ? 'technician' : 'staff') : 'customer',
            'comment' => trim($request->comment),
        ]);

        $comment->load(['replies', 'user']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Comment posted successfully.',
                'comment' => $comment,
            ]);
        }

        return back()->with('success', 'Your comment on the picture was submitted successfully.');
    }

    /**
     * Store comment or reply from Technician / Staff Workspace (Authenticated staff)
     */
    public function storeStaffComment(Request $request, JobOrder $jobOrder)
    {
        $request->validate([
            'photo_type' => 'required|string|in:progress_photo,attachment',
            'photo_id' => 'required|integer',
            'comment' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:photo_comments,id',
        ]);

        $modelClass = $request->photo_type === 'attachment' 
            ? Attachment::class 
            : RepairProgressPhoto::class;

        $photo = $modelClass::findOrFail($request->photo_id);
        $user = Auth::user();

        $authorType = $user->role === 'technician' ? 'technician' : 'staff';
        $authorName = $user->name . ' (' . ucfirst($user->role ?? 'Staff') . ')';

        $comment = PhotoComment::create([
            'job_order_id' => $jobOrder->id,
            'photo_type' => $modelClass,
            'photo_id' => $photo->id,
            'parent_id' => $request->parent_id,
            'user_id' => $user->id,
            'author_name' => $authorName,
            'author_type' => $authorType,
            'comment' => trim($request->comment),
        ]);

        $comment->load(['replies', 'user']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Reply posted successfully.',
                'comment' => $comment,
            ]);
        }

        return back()->with('success', 'Reply posted on picture.');
    }
}
