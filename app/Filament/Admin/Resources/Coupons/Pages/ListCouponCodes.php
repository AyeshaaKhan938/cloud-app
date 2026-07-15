<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Coupons\Pages;

use App\Filament\Admin\Resources\Coupons\CouponResource;
use App\Models\CouponCode;
use App\Services\Coupons\CouponQrSvgRenderer;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable as InteractsWithTableConcern;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

final class ListCouponCodes extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTableConcern;

    protected static string $resource = CouponResource::class;

    protected static ?string $breadcrumb = 'Coupon codes';

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $this->mountInteractsWithTable();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(self::getResource()::canView($this->getRecord()), 403);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Codes: '.$this->getRecord()->name;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to coupons')
                ->url(CouponResource::getUrl())
                ->color('gray'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Conversion code')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('usage')
                    ->label('Usage')
                    ->formatStateUsing(function (mixed $state, TextColumn $column): string {
                        $record = $column->getRecord();

                        if (! $record instanceof CouponCode) {
                            return '';
                        }

                        return $record->usageLabel();
                    }),
            ])
            ->recordActions([
                Action::make('viewQr')
                    ->label('View QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->modalWidth(Width::Small)
                    ->modalHeading('QR code')
                    ->modalContent(function (CouponCode $record): HtmlString {
                        $svg = app(CouponQrSvgRenderer::class)->render($record->code);

                        return new HtmlString('<div class="fi-ta-qr flex max-w-[220px] justify-center p-4 [&_svg]:h-auto [&_svg]:max-w-full">'.$svg.'</div>');
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    protected function getTableQuery(): Builder
    {
        return CouponCode::query()
            ->where('coupon_id', $this->getRecord()->getKey())
            ->orderBy('id');
    }
}
