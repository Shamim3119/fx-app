<div
    x-data="{
        search: '',

        countries: @js(
            $countries->map(function ($country) {
                return [
                    'id' => $country->id,
                    'name' => $country->name,
                    'prefix' => $country->prefix,
                    'code' => $country->code,
                    'img' => $country->img,
                ];
            })->values()
        ),

        get filteredCountries() {
            const search = this.search.toLowerCase().trim();

            if (!search) {
                return this.countries;
            }

            return this.countries.filter(country => {
                return (
                    (country.name ?? '').toLowerCase().includes(search) ||
                    (country.prefix ?? '').toLowerCase().includes(search) ||
                    (country.code ?? '').toLowerCase().includes(search)
                );
            });
        }
    }"

    class="currency-popup"
>

    {{-- ========================================================= --}}
    {{-- SEARCH --}}
    {{-- ========================================================= --}}

    <div class="currency-search-wrapper">

        <div class="currency-search">

            {{-- Search icon --}}
            <svg
                class="currency-search-icon"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.8"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m21 21-4.5-4.5m0 0A7.5 7.5 0 1 0 6 6a7.5 7.5 0 0 0 10.5 10.5Z"
                />
            </svg>


            {{-- Search input --}}
            <input
                type="text"
                x-model="search"
                class="currency-search-input"
                placeholder="Search country, prefix or code..."
            />


            {{-- Clear search --}}
            <button
                type="button"
                x-show="search.length > 0"
                x-cloak
                @click="search = ''"
                class="currency-search-clear"
            >
                ×
            </button>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- COUNTRY TABLE --}}
    {{-- ========================================================= --}}

    <div class="currency-table-container">

        <table class="currency-table">

            {{-- TABLE HEADER --}}
            <thead>

                <tr>

                    <th class="sl-column">
                        SL
                    </th>

                    <th class="country-column">
                        Country
                    </th>

                    <th class="prefix-column">
                        Prefix
                    </th>

                    <th class="code-column">
                        Code
                    </th>

                    <th class="image-column">
                        Image
                    </th>

                    <th class="action-column">
                        Action
                    </th>

                </tr>

            </thead>


            {{-- TABLE BODY --}}
            <tbody>

                <template
                    x-for="(country, index) in filteredCountries"
                    :key="country.id"
                >

                    <tr>

                        {{-- SL --}}
                        <td class="sl-column">

                            <span
                                x-text="index + 1"
                            ></span>

                        </td>


                        {{-- COUNTRY --}}
                        <td class="country-column">

                            <div class="country-name">

                                <span
                                    x-text="country.name"
                                ></span>

                            </div>

                        </td>


                        {{-- PREFIX --}}
                        <td class="prefix-column">

                            <span
                                x-text="country.prefix || '-'"
                            ></span>

                        </td>


                        {{-- CODE --}}
                        <td class="code-column">

                            <template x-if="country.code">

                                <span
                                    class="country-code"
                                    x-text="country.code"
                                ></span>

                            </template>

                            <template x-if="!country.code">

                                <span>
                                    -
                                </span>

                            </template>

                        </td>


                        {{-- IMAGE --}}
                        <td class="image-column">

                            <template x-if="country.img">

                                <img
                                    :src="'{{ asset('storage') }}/' + country.img"
                                    :alt="country.name"
                                    class="country-image"
                                >

                            </template>


                            <template x-if="!country.img">

                                <div class="country-placeholder">

                                    <span
                                        x-text="
                                            country.name
                                                .charAt(0)
                                                .toUpperCase()
                                        "
                                    ></span>

                                </div>

                            </template>

                        </td>


                        {{-- ACTION --}}
                        <td class="action-column">

                            <button
                                type="button"
                                class="add-currency-button"
                                @click="$wire.addCurrency(country.id)"
                            >
                                Add
                            </button>

                        </td>

                    </tr>

                </template>


                {{-- NO RESULTS --}}
                <tr
                    x-show="filteredCountries.length === 0"
                >

                    <td
                        colspan="6"
                        class="currency-empty"
                    >

                        <div class="empty-icon">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m21 21-4.5-4.5m0 0A7.5 7.5 0 1 0 6 6a7.5 7.5 0 0 0 10.5 10.5Z"
                                />
                            </svg>

                        </div>


                        <div class="empty-title">
                            No countries found
                        </div>


                        <div class="empty-text">
                            Try another country name, prefix or code.
                        </div>

                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    {{-- ========================================================= --}}
    {{-- RESULT COUNT --}}
    {{-- ========================================================= --}}

    <div class="currency-count">

        <span>

            Showing

            <strong
                x-text="filteredCountries.length"
            ></strong>

            inactive countries

        </span>


        <span
            x-show="search.length > 0"
        >

            Search:

            <strong
                x-text="search"
            ></strong>

        </span>

    </div>


    {{-- ========================================================= --}}
    {{-- CSS --}}
    {{-- ========================================================= --}}

    <style>

        /* =======================================================
           MAIN
        ======================================================= */

        .currency-popup {
            width: 100%;
        }


        /* =======================================================
           SEARCH
        ======================================================= */

        .currency-search-wrapper {
            width: 100%;
            margin-bottom: 18px;
        }

        .currency-search {
            position: relative;
            width: 100%;
        }

        .currency-search-input {
            width: 100% !important;
            height: 44px !important;

            box-sizing: border-box !important;

            padding: 0 42px 0 42px !important;

            border: 1px solid #d1d5db !important;

            border-radius: 8px !important;

            background: #ffffff !important;

            color: #111827 !important;

            font-size: 14px !important;

            outline: none !important;
        }

        .currency-search-input::placeholder {
            color: #9ca3af !important;
        }

        .currency-search-input:focus {
            border-color: #3b82f6 !important;

            box-shadow:
                0 0 0 2px
                rgba(59, 130, 246, 0.15) !important;
        }

        .currency-search-icon {
            position: absolute;

            left: 13px;
            top: 50%;

            width: 19px;
            height: 19px;

            transform: translateY(-50%);

            color: #9ca3af;

            pointer-events: none;

            z-index: 2;
        }

        .currency-search-clear {
            position: absolute;

            right: 10px;
            top: 50%;

            width: 28px;
            height: 28px;

            transform: translateY(-50%);

            border: 0;

            background: transparent;

            color: #9ca3af;

            font-size: 22px;

            line-height: 28px;

            text-align: center;

            cursor: pointer;
        }

        .currency-search-clear:hover {
            color: #374151;
        }


        /* =======================================================
           TABLE CONTAINER
        ======================================================= */

        .currency-table-container {
            width: 100%;

            max-height: 500px;

            overflow-x: auto;

            overflow-y: auto;

            border: 1px solid #e5e7eb;

            border-radius: 10px;

            background: #ffffff;
        }


        /* =======================================================
           TABLE
        ======================================================= */

        .currency-table {
            width: 100%;

            min-width: 720px;

            border-collapse: collapse;

            table-layout: fixed;

            font-size: 14px;
        }


        /* =======================================================
           HEADER
        ======================================================= */

        .currency-table thead {
            position: sticky;

            top: 0;

            z-index: 5;

            background: #f8fafc;
        }

        .currency-table th {
            height: 46px;

            padding: 0 14px;

            border-bottom: 1px solid #e5e7eb;

            color: #374151;

            font-size: 12px;

            font-weight: 600;

            text-align: left;

            vertical-align: middle;

            white-space: nowrap;
        }


        /* =======================================================
           BODY
        ======================================================= */

        .currency-table td {
            height: 58px;

            padding: 8px 14px;

            border-bottom: 1px solid #f0f0f0;

            color: #4b5563;

            vertical-align: middle;
        }

        .currency-table tbody tr:hover {
            background: #f9fafb;
        }

        .currency-table tbody tr:last-child td {
            border-bottom: 0;
        }


        /* =======================================================
           COLUMNS
        ======================================================= */

        .currency-table .sl-column {
            width: 60px;

            text-align: center;
        }

        .currency-table .country-column {
            width: auto;
        }

        .currency-table .prefix-column {
            width: 110px;
        }

        .currency-table .code-column {
            width: 100px;
        }

        .currency-table .image-column {
            width: 100px;
        }

        .currency-table .action-column {
            width: 110px;

            text-align: right;
        }


        /* =======================================================
           COUNTRY NAME
        ======================================================= */

        .country-name {
            color: #111827;

            font-weight: 600;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        /* =======================================================
           CODE
        ======================================================= */

        .country-code {
            display: inline-block;

            padding: 4px 8px;

            border-radius: 5px;

            background: #f3f4f6;

            color: #374151;

            font-size: 11px;

            font-weight: 600;
        }


        /* =======================================================
           IMAGE
        ======================================================= */

        .country-image {
            display: block;

            width: 36px;
            height: 36px;

            border-radius: 50%;

            object-fit: cover;

            border: 1px solid #e5e7eb;
        }

        .country-placeholder {
            width: 36px;
            height: 36px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #f3f4f6;

            color: #6b7280;

            font-weight: 600;
        }


        /* =======================================================
           ADD BUTTON
        ======================================================= */

        .add-currency-button {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 58px;

            height: 34px;

            padding: 0 13px;

            border: 0 !important;

            border-radius: 6px !important;

            background: #2563eb !important;

            color: #ffffff !important;

            font-size: 13px !important;

            font-weight: 600 !important;

            line-height: 1 !important;

            cursor: pointer;

            transition:
                background 0.15s ease,
                transform 0.1s ease;
        }

        .add-currency-button:hover {
            background: #1d4ed8 !important;
        }

        .add-currency-button:active {
            transform: scale(0.97);
        }


        /* =======================================================
           EMPTY
        ======================================================= */

        .currency-empty {
            padding: 50px 20px !important;

            text-align: center !important;
        }

        .empty-icon {
            width: 44px;
            height: 44px;

            margin: 0 auto 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #f3f4f6;

            color: #9ca3af;
        }

        .empty-icon svg {
            width: 22px;
            height: 22px;
        }

        .empty-title {
            color: #111827;

            font-size: 14px;

            font-weight: 600;
        }

        .empty-text {
            margin-top: 4px;

            color: #9ca3af;

            font-size: 12px;
        }


        /* =======================================================
           COUNT
        ======================================================= */

        .currency-count {
            display: flex;

            justify-content: space-between;

            margin-top: 10px;

            color: #6b7280;

            font-size: 12px;
        }

        .currency-count strong {
            color: #374151;

            font-weight: 600;
        }


        /* =======================================================
           DARK MODE
        ======================================================= */

        .dark .currency-search-input {
            border-color: #374151 !important;

            background: #111827 !important;

            color: #f9fafb !important;
        }

        .dark .currency-search-input::placeholder {
            color: #6b7280 !important;
        }

        .dark .currency-table-container {
            border-color: #374151;

            background: #111827;
        }

        .dark .currency-table thead {
            background: #1f2937;
        }

        .dark .currency-table th {
            border-color: #374151;

            color: #d1d5db;
        }

        .dark .currency-table td {
            border-color: #374151;

            color: #9ca3af;
        }

        .dark .currency-table tbody tr:hover {
            background: #1f2937;
        }

        .dark .country-name {
            color: #f9fafb;
        }

        .dark .country-code {
            background: #374151;

            color: #e5e7eb;
        }

        .dark .country-placeholder {
            background: #374151;

            color: #d1d5db;
        }

        .dark .currency-count strong {
            color: #d1d5db;
        }

    </style>

</div>