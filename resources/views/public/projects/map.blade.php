@extends('public.layouts.app')
@section('name', 'page project-map-page')
@section('title', 'Project map — 21-LMS')
@section('header-class', 'project-map-site-header')
@section('map-toggle-active', 'active')
@section('content')

    <!-- Notifications overlay -->
    @include('public.components.notifications-overlay')

    @include('public.components.user-menu-overlay')

    <!-- Activities overlay -->
    @include('public.components.activities-overlay')

    <!-- Projects overlay -->
    @include('public.components.projects-overlay')

    <main class="project-map-main">
        <div class="project-map-header">
            <a href="{{ route('public.projects.index') }}" class="project-map-close" aria-label="Back to projects">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            </a>
            <div class="project-map-search">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" placeholder="Search project" id="projectMapSearch" />
                <button class="project-map-filter" aria-label="Filter">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg>
                </button>
            </div>
        </div>

        <div class="project-map-toolbar">
            <button class="map-tool-btn map-view-toggle" id="mapViewToggle" title="Switch view">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </button>
            <button class="map-tool-btn map-fit" id="mapFit" title="Fit to screen">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
            </button>
            <button class="map-tool-btn map-zoom-in" id="mapZoomIn" title="Zoom in">+</button>
            <button class="map-tool-btn map-zoom-out" id="mapZoomOut" title="Zoom out">−</button>
        </div>

        <div class="project-map-container" id="projectMapContainer">
            <div class="project-map-stage" id="projectMapStage">
                <div class="map-linear" id="mapLinear">
                    <div class="linear-track"></div>
                    <div class="linear-nodes" id="linearNodes"></div>
                </div>
                <div class="map-branching active" id="mapBranching">
                    <svg class="branching-svg" id="coreMapSvg" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid meet">
                        <g id="coreTrunks"></g>
                        <g id="coreNodes"></g>
                    </svg>
                </div>
            </div>
            <button class="map-start-button" id="mapStartButton" type="button">Start Common Core</button>
        </div>
    </main>

    <div class="project-map-tooltip" id="projectMapTooltip">
        <div class="map-tooltip-head">
            <span class="map-tooltip-type" id="tooltipType">Individual</span>
            <span class="map-tooltip-status" id="tooltipStatus">Available</span>
        </div>
        <div class="map-tooltip-title" id="tooltipTitle">T13D22 <span>150 XP</span></div>
        <p class="map-tooltip-desc" id="tooltipDesc">This day will help you get acquainted with text files processing.</p>
        <div class="map-tooltip-tags" id="tooltipTags"></div>
        <div class="map-tooltip-meta">
            <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <span id="tooltipDays">1 day</span></span>
            <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> <span id="tooltipHours">38 hours</span></span>
        </div>
    </div>

@endsection
