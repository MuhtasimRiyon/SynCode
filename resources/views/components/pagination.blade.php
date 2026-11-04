@if($pagination->hasPages())
<nav class="filter-row" aria-label="Story pages">
@if($pagination->previousPageUrl())<a class="button secondary" href="{{ $pagination->previousPageUrl() }}">← Previous</a>@endif
<span>Page {{ $pagination->currentPage() }} of {{ $pagination->lastPage() }}</span>
@if($pagination->nextPageUrl())<a class="button secondary" href="{{ $pagination->nextPageUrl() }}">Next →</a>@endif
</nav>
@endif
