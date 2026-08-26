<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Resources\Countries\CountryResource;
use App\Models\Country;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCountries extends ListRecords
{
    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Action::make('addCurrency')
                ->label('Add Currency')
                ->icon('heroicon-o-plus')
                ->color('primary')

                ->modalHeading('Add Currency')

                ->modalDescription(
                    'Search and select a country to add it to active currencies.'
                )

                ->modalWidth('6xl')

                ->modalSubmitAction(false)

                ->modalCancelActionLabel('Close')

                ->modalContent(function () {

                    return view(
                        'filament.currencies.add-currency',
                        [
                            'countries' => Country::query()
                                ->where('inactive', 1)
                                ->orderBy('name')
                                ->get(),
                        ]
                    );

                }),

        ];
    }


    public function addCurrency(int $countryId): void
    {
        $country = Country::query()
            ->where('id', $countryId)
            ->where('inactive', 1)
            ->first();

        if (! $country) {

            Notification::make()
                ->title('Country not found')
                ->danger()
                ->send();

            return;
        }


        /*
         * Activate currency
         */
        $country->update([
            'inactive' => 0,
            'currency_type' => 1,
        ]);


        /*
         * Success notification
         */
        Notification::make()
            ->title('Currency Added')
            ->body(
                $country->name . ' has been added to active currencies.'
            )
            ->success()
            ->send();


        /*
         * Refresh Currency table
         */
        $this->dispatch('$refresh');


        /*
         * Close Filament modal
         *
         * We search inside the currently open dialog
         * and click its close button.
         */
        $this->js(<<<'JS'

            setTimeout(() => {

                const dialogs = document.querySelectorAll(
                    '[role="dialog"]'
                );

                const dialog =
                    dialogs[dialogs.length - 1];

                if (! dialog) {
                    return;
                }


                /*
                 * Try Filament close button
                 */
                let closeButton =
                    dialog.querySelector(
                        'button[aria-label="Close modal"]'
                    );


                /*
                 * Alternative aria label
                 */
                if (! closeButton) {

                    closeButton =
                        dialog.querySelector(
                            'button[aria-label="Close"]'
                        );

                }


                /*
                 * Filament Alpine close handler
                 */
                if (! closeButton) {

                    closeButton =
                        dialog.querySelector(
                            'button[x-on\\:click*="close"]'
                        );

                }


                /*
                 * Click close button
                 */
                if (closeButton) {

                    closeButton.click();

                }

            }, 100);

        JS);
    }
}