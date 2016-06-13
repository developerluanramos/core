@extends('app')
@inject('modules', 'Dias\Services\Modules')

@section('title') Annotate @stop

@push('scripts')
<script src="{{ asset('vendor/annotations/scripts/ol.js') }}"></script>
<script src="{{ asset('vendor/annotations/scripts/main.js') }}"></script>
<script type="text/javascript">
    angular.module('dias.annotations').constant('IMAGE_ID', {{$image->id}});
    angular.module('dias.annotations').constant('EDIT_MODE', {{$editMode ? 'true' : 'false'}});
    angular.module('dias.annotations').constant('TRANSECT_ID', {{$image->transect_id}});
    angular.module('dias.annotations').constant('IMAGES_IDS', {!!$images->keys()!!});
    angular.module('dias.annotations').constant('IMAGES_FILENAMES', {!!$images->values()!!});
    angular.module('dias.annotations').constant('LABEL_TREES', {!!$labelTrees!!});
</script>
@foreach ($modules->getMixins('annotationsScripts') as $module => $nestedMixins)
    @include($module.'::annotationsScripts', ['mixins' => $nestedMixins])
@endforeach
@endpush

@push('styles')
<link href="{{ asset('vendor/annotations/styles/ol.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/annotations/styles/main.css') }}" rel="stylesheet">
@foreach ($modules->getMixins('annotationsStyles') as $module => $nestedMixins)
    @include($module.'::annotationsStyles', ['mixins' => $nestedMixins])
@endforeach
@endpush

@section('navbar')
<div class="navbar-text navbar-annotations-breadcrumbs">
    @if ($transect->projects->count() > 1)
        <span class="dropdown">
            <a href="#" class="dropdown-toggle navbar-link">Projects <span class="caret"></span></a>
            <ul class="dropdown-menu">
                @foreach ($transect->projects as $project)
                    <li><a href="{{route('project', $project->id)}}">{{$project->name}}</a></li>
                @endforeach
            </ul>
        </span>
    @else
        <a href="{{route('project', $transect->projects->first()->id)}}" class="navbar-link" title="Show project {{$transect->projects->first()->name}}">{{$transect->projects->first()->name}}</a>
    @endif
    / <a href="{{route('transect', $transect->id)}}" class="navbar-link" title="Show transect {{$transect->name}}">{{$transect->name}}</a>
    / <strong class="navbar-annotations-filename">{{$image->filename}}</strong>
</div>
@endsection

@section('content')
<div class="annotator__container" data-ng-app="dias.annotations" data-ng-controller="AnnotatorController">
    <div id="canvas" class="annotator__canvas" data-ng-controller="CanvasController">
        <div class="canvas__loader" data-ng-class="{active:imageLoading}"></div>
            {{--
            @if ($editMode)
                <div class="confidence-control" data-ng-controller="ConfidenceController" title="Label confidence: @{{ confidence | number:2 }}">
                    <span class="label confidence-label" data-ng-class="confidenceClass" data-ng-bind="confidence | number:2"></span>
                    <input class="confidence-range" type="range" min="0.01" max="1.0" step="0.01" data-ng-model="confidence">
                </div>
            @endif
            --}}
            <div class="controls-bar">
                @include('annotations::index.controls.navigation')
                @if ($editMode)
                    @include('annotations::index.controls.drawing')
                    @include('annotations::index.controls.edit')
                @endif
            </div>
            @if ($editMode)
                <div class="ng-cloak selected-label" data-ng-controller="SelectedLabelController" title="Currently selected label" data-ng-bind="getSelectedLabel().name" data-ng-show="hasSelectedLabel()"></div>
            @endif
            <div class="fullscreen__minimap" data-ng-controller="MinimapController"></div>
    </div>
    @include('annotations::index.sidebar')
</div>
@endsection
