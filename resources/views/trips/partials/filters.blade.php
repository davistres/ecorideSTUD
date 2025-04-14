<!-- Si il y a 2 résultats ou plus => les filtres -->
@if (isset($covoiturages) && count($covoiturages) >= 2)
    <section class="filters-section">
        <h2 class="filters-title">Filtrer les résultats</h2>

        <div class="filters-container">
            <!-- Filtre éco -->
            <div class="filter-group">
                <label for="eco-filter" class="filter-label-checkbox filter-label">
                    <input type="checkbox" id="eco-filter" class="filter-checkbox">
                    <span class="filter-text">Uniquement les voyages écologiques</span>
                </label>
            </div>

            <!-- Filtre prix -->
            <div class="filter-group">
                <label for="price-filter" class="filter-label">Prix maximum:
                    <div><span id="price-value">{{ $max_price }}</span> crédits</div>
                </label>
                <input type="range" id="price-filter" class="filter-range" min="{{ $min_price }}"
                    max="{{ $max_price }}" value="{{ $max_price }}" step="1">
                <div class="range-labels">
                    <span class="range-min">{{ $min_price }}</span>
                    <span class="range-max">{{ $max_price }}</span>
                </div>
            </div>

            <!-- Filtre temps -->
            <div class="filter-group">
                <label for="duration-filter" class="filter-label">Durée maximale: <span id="duration-value"></span>
                </label>
                <input type="range" id="duration-filter" class="filter-range" min="{{ $min_duration }}"
                    max="{{ $max_duration }}" value="{{ $max_duration }}" step="1">
                <div class="range-labels">
                    <span class="range-min">{{ $min_duration_formatted }}</span>
                    <span class="range-max">{{ $max_duration_formatted }}</span>
                </div>
            </div>

            <!-- Filtre note -->
            <div class="filter-rating-flex filter-group">
                <label for="rating-filter" class="filter-label">Note minimale <br>du conducteur:</label>
                <div class="rating-filter">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                    <input type="hidden" id="rating-filter" value="0">
                </div>
            </div>
        </div>
    </section>
@endif
