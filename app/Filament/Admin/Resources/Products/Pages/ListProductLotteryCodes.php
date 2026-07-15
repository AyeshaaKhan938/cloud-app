<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductLotteryResource;
use App\Models\ProductLotteryCode;
use App\Services\Coupons\CouponQrSvgRenderer;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable as InteractsWithTableConcern;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

final class ListProductLotteryCodes extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTableConcern;

    protected static string $resource = ProductLotteryResource::class;

    protected static ?string $breadcrumb = 'Lottery codes';

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
                ->label('Back to lotteries')
                ->url(ProductLotteryResource::getUrl())
                ->color('gray'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('prize.tier_code')
                    ->label('Tier')
                    ->sortable(),
                TextColumn::make('prize.name')
                    ->label('Prize name')
                    ->toggleable(),
                TextColumn::make('prize.prize_amount')
                    ->label('Prize value')
                    ->money('USD')
                    ->sortable(),
                IconColumn::make('is_redeemed')
                    ->label('Redeemed')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('danger')
                    ->falseIcon(Heroicon::OutlinedCheckCircle)
                    ->falseColor('success')
                    ->getStateUsing(fn (ProductLotteryCode $record): bool => $record->isRedeemed()),
                TextColumn::make('redeemed_at')
                    ->label('Redeemed at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('viewQr')
                    ->label('View QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->modalWidth(Width::Small)
                    ->modalHeading('QR code')
                    ->modalContent(function (ProductLotteryCode $record): HtmlString {
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
        return ProductLotteryCode::query()
            ->where('product_lottery_id', $this->getRecord()->getKey())
            ->with('prize')
            ->orderBy('id');
    }
}
