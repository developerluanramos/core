<?php

namespace Dias\Http\Controllers\Views;

use Dias\AnnotationLabel;
use Dias\ImageLabel;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard to the user.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $annotationLabel = AnnotationLabel::where('user_id', $this->user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $imageLabel = ImageLabel::where('user_id', $this->user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($annotationLabel) {
            $recentImage = $annotationLabel->annotation->image;
        } else {
            $recentImage = null;
        }

        if ($imageLabel) {
            if ($annotationLabel && $annotationLabel->created_at > $imageLabel->created_at) {
                // if the annotation label is newer than the image label,
                // take its transect
                $recentTransect = $recentImage->transect;
            } else {
                // else take the transect of the image label
                $recentTransect = $imageLabel->image->transect;
            }
            $recentTransectImage = $recentTransect->images()->first();
        } elseif ($recentImage) {
            $recentTransect = $recentImage->transect;
            $recentTransectImage = $recentTransect->images()->first();
        } else {
            $recentTransect = null;
            $recentTransectImage = null;
        }

        $projects = $this->user->projects()
            ->orderBy('updated_at', 'desc')
            ->take(3)
            ->get();

        return view('dashboard', [
            'user' => $this->user,
            'recentImage' => $recentImage,
            'recentTransect' => $recentTransect,
            'recentTransectImage' => $recentTransectImage,
            'projects' => $projects,
        ]);
    }
}
