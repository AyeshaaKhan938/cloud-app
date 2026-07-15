<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Crea 40 productos de prueba y los asigna a los slots de la primera máquina.
 *
 * Correr: php artisan db:seed --class=VendingProductSeeder
 */
class VendingProductSeeder extends Seeder
{
    // ── Test machine number ───────────────────────────────────────────────────
    private const MACHINE_NO = '866903255700003';

    // ── Product catalog ───────────────────────────────────────────────────────

    private function catalog(): array
    {
        // IDs curados de picsum.photos — determinísticos, sin sorpresas.
        // Ver galería: https://picsum.photos/images
        $p = fn (int $id, int $w = 400, int $h = 400) => "https://picsum.photos/id/{$id}/{$w}/{$h}";

        return [
            // ── Beverages ────────────────────────────────────────────────────
            [
                'name' => 'Coca-Cola',
                'sku' => 'BEV-001',
                'description' => 'Classic Coca-Cola 12 fl oz can. Ice cold refreshment anytime.',
                'price' => 2.50,
                'brand' => 'Coca-Cola',
                'main_image' => $p(431),          // espresso/coffee cup – warm red tones
                'media_expansions' => [$p(312), $p(442)],
            ],
            [
                'name' => 'Pepsi',
                'sku' => 'BEV-002',
                'description' => 'Pepsi-Cola 12 fl oz can. Bold, refreshing taste.',
                'price' => 2.50,
                'brand' => 'PepsiCo',
                'main_image' => $p(326),          // blue bokeh drink
                'media_expansions' => [$p(374)],
            ],
            [
                'name' => 'Sprite',
                'sku' => 'BEV-003',
                'description' => 'Crisp lemon-lime flavor 12 fl oz. Crisp. Clean. Refreshing.',
                'price' => 2.50,
                'brand' => 'Coca-Cola',
                'main_image' => $p(1003),         // fresh green lime
                'media_expansions' => [],
            ],
            [
                'name' => 'Water Bottle 16oz',
                'sku' => 'BEV-004',
                'description' => 'Pure natural spring water. Hydrate on the go.',
                'price' => 1.75,
                'brand' => 'Aquafina',
                'main_image' => $p(1058),         // clear water splash
                'media_expansions' => [],
            ],
            [
                'name' => 'Monster Energy',
                'sku' => 'BEV-005',
                'description' => 'Monster Energy Original 16 fl oz. Unleash the beast!',
                'price' => 3.50,
                'brand' => 'Monster Beverage',
                'main_image' => $p(219),          // dark dramatic can
                'media_expansions' => [$p(237)],
            ],
            [
                'name' => 'Red Bull',
                'sku' => 'BEV-006',
                'description' => 'Red Bull Energy Drink 8.4 fl oz. Gives you wings.',
                'price' => 3.25,
                'brand' => 'Red Bull',
                'main_image' => $p(490),          // vibrant bright
                'media_expansions' => [],
            ],
            [
                'name' => 'Orange Juice',
                'sku' => 'BEV-007',
                'description' => 'Tropicana Pure Premium orange juice 10 fl oz. 100% pure squeezed.',
                'price' => 3.00,
                'brand' => 'Tropicana',
                'main_image' => $p(488),          // orange fruit
                'media_expansions' => [],
            ],
            [
                'name' => 'Gatorade Lemon',
                'sku' => 'BEV-008',
                'description' => 'Gatorade Thirst Quencher Lemon-Lime 20 fl oz. Replenish electrolytes.',
                'price' => 2.75,
                'brand' => 'Gatorade',
                'main_image' => $p(1066),         // sport/gym
                'media_expansions' => [],
            ],
            [
                'name' => 'Sparkling Water',
                'sku' => 'BEV-009',
                'description' => 'LaCroix Natural Sparkling Water 12 fl oz. Zero calories, zero sugar.',
                'price' => 2.25,
                'brand' => 'LaCroix',
                'main_image' => $p(1078),         // water bubbles
                'media_expansions' => [],
            ],
            [
                'name' => 'Iced Coffee',
                'sku' => 'BEV-010',
                'description' => 'Starbucks Double Shot Espresso 6.5 fl oz. Premium chilled coffee.',
                'price' => 4.00,
                'brand' => 'Starbucks',
                'main_image' => $p(30),           // coffee latte art
                'media_expansions' => [$p(766)],
            ],

            // ── Snacks ────────────────────────────────────────────────────────
            [
                'name' => "Lay's Classic",
                'sku' => 'SNK-001',
                'description' => "Lay's Classic Potato Chips 1.5 oz. Light, crispy, perfectly salted.",
                'price' => 1.75,
                'brand' => "Lay's",
                'main_image' => $p(292),          // appetizing food
                'media_expansions' => [],
            ],
            [
                'name' => 'Doritos Nacho',
                'sku' => 'SNK-002',
                'description' => 'Doritos Nacho Cheese 1.75 oz. Boldly cheesy flavor.',
                'price' => 1.75,
                'brand' => 'Doritos',
                'main_image' => $p(429),          // warm food tones
                'media_expansions' => [],
            ],
            [
                'name' => 'Cheetos Crunchy',
                'sku' => 'SNK-003',
                'description' => 'Cheetos Crunchy 1.75 oz. Dangerously cheesy.',
                'price' => 1.75,
                'brand' => 'Cheetos',
                'main_image' => $p(1080),         // orange/food
                'media_expansions' => [],
            ],
            [
                'name' => 'Pringles Original',
                'sku' => 'SNK-004',
                'description' => 'Pringles Original 2.5 oz. Once you pop, you can\'t stop.',
                'price' => 2.25,
                'brand' => 'Pringles',
                'main_image' => $p(999),          // food closeup
                'media_expansions' => [],
            ],
            [
                'name' => 'Oreo Cookies',
                'sku' => 'SNK-005',
                'description' => 'Oreo Original Sandwich Cookies 2 oz. Twist, lick, dunk.',
                'price' => 2.00,
                'brand' => 'Nabisco',
                'main_image' => $p(312),          // dark chocolate tones
                'media_expansions' => [],
            ],
            [
                'name' => 'Snickers Bar',
                'sku' => 'SNK-006',
                'description' => 'Snickers Full Size Candy Bar 1.86 oz. Packed with peanuts, caramel, nougat and chocolate.',
                'price' => 2.00,
                'brand' => 'Mars',
                'main_image' => $p(493),          // warm caramel/brown
                'media_expansions' => [],
            ],
            [
                'name' => 'Kit Kat',
                'sku' => 'SNK-007',
                'description' => 'Kit Kat Wafer Bar 1.5 oz. Give me a break!',
                'price' => 1.75,
                'brand' => "Hershey's",
                'main_image' => $p(1082),         // chocolate/red
                'media_expansions' => [],
            ],
            [
                'name' => 'M&Ms Peanut',
                'sku' => 'SNK-008',
                'description' => "M&M's Peanut Chocolate Candies 1.74 oz. Melts in your mouth, not in your hands.",
                'price' => 2.00,
                'brand' => 'Mars',
                'main_image' => $p(1060),         // colorful candy
                'media_expansions' => [],
            ],
            [
                'name' => 'Skittles',
                'sku' => 'SNK-009',
                'description' => 'Skittles Original Fruit Candy 2.17 oz. Taste the rainbow.',
                'price' => 1.75,
                'brand' => 'Skittles',
                'main_image' => $p(1084),         // colorful fruit bowl
                'media_expansions' => [],
            ],
            [
                'name' => "Reese's Cups",
                'sku' => 'SNK-010',
                'description' => "Reese's Peanut Butter Cups 1.5 oz. Two of the greatest tastes that taste great together.",
                'price' => 2.00,
                'brand' => "Reese's",
                'main_image' => $p(635),          // peanut/brown tones
                'media_expansions' => [],
            ],
            [
                'name' => 'Granola Bar',
                'sku' => 'SNK-011',
                'description' => "Nature Valley Crunchy Oats 'n Honey Granola Bar 1.5 oz. Made with whole grain oats.",
                'price' => 1.75,
                'brand' => 'Nature Valley',
                'main_image' => $p(139),          // oats/wheat tones
                'media_expansions' => [],
            ],
            [
                'name' => 'Kind Bar Almond',
                'sku' => 'SNK-012',
                'description' => 'Kind Bar Almond & Coconut 1.4 oz. Gluten free, non-GMO, no artificial flavors.',
                'price' => 2.50,
                'brand' => 'Kind',
                'main_image' => $p(1055),         // nuts closeup
                'media_expansions' => [],
            ],
            [
                'name' => 'Trail Mix',
                'sku' => 'SNK-013',
                'description' => 'Planters Trail Mix 2 oz. A satisfying blend of nuts, seeds, and dried fruit.',
                'price' => 2.25,
                'brand' => 'Planters',
                'main_image' => $p(429),          // mixed nuts
                'media_expansions' => [],
            ],
            [
                'name' => 'Beef Jerky',
                'sku' => 'SNK-014',
                'description' => "Jack Link's Original Beef Jerky 1.25 oz. High protein snack. 100% beef.",
                'price' => 3.50,
                'brand' => "Jack Link's",
                'main_image' => $p(1072),         // grilled/meat closeup
                'media_expansions' => [],
            ],
            [
                'name' => 'Popcorn',
                'sku' => 'SNK-015',
                'description' => 'Smartfood White Cheddar Popcorn 1.75 oz. Real white cheddar cheese popcorn.',
                'price' => 1.75,
                'brand' => 'Smartfood',
                'main_image' => $p(1083),         // light/food
                'media_expansions' => [],
            ],

            // ── Electronics & Accessories ──────────────────────────────────────
            [
                'name' => 'USB-C Cable 3ft',
                'sku' => 'ELC-001',
                'description' => 'Anker USB-C to USB-A Fast Charging Cable 3ft. Compatible with Samsung, Pixel, iPad, and more.',
                'price' => 8.99,
                'brand' => 'Anker',
                'main_image' => $p(160),          // cables/tech
                'media_expansions' => [$p(366)],
            ],
            [
                'name' => 'Earbuds',
                'sku' => 'ELC-002',
                'description' => 'Wired earbuds with microphone. Universal 3.5mm jack. Crystal clear audio.',
                'price' => 9.99,
                'brand' => 'JLab',
                'main_image' => $p(0),            // tech/lifestyle
                'media_expansions' => [$p(180)],
            ],
            [
                'name' => 'Phone Stand',
                'sku' => 'ELC-003',
                'description' => 'Adjustable smartphone stand. Compatible with all phones. Foldable and portable.',
                'price' => 6.99,
                'brand' => 'Lamicall',
                'main_image' => $p(2),            // laptop/desk
                'media_expansions' => [],
            ],
            [
                'name' => 'Power Bank 5000mAh',
                'sku' => 'ELC-004',
                'description' => 'Portable charger 5000mAh. Charges your phone 1-2x. USB-A output.',
                'price' => 14.99,
                'brand' => 'Anker',
                'main_image' => $p(9),            // tech device
                'media_expansions' => [$p(119), $p(375)],
            ],
            [
                'name' => 'Screen Cleaner Kit',
                'sku' => 'ELC-005',
                'description' => 'Microfiber screen cleaning kit. Safe for all screens — phones, tablets, laptops.',
                'price' => 4.99,
                'brand' => 'Care Touch',
                'main_image' => $p(1029),         // clean/minimal
                'media_expansions' => [],
            ],
            [
                'name' => 'Lightning Cable 6ft',
                'sku' => 'ELC-006',
                'description' => 'Apple MFi Certified Lightning Cable 6ft. For iPhone, iPad, iPod.',
                'price' => 11.99,
                'brand' => 'Anker',
                'main_image' => $p(341),          // cable/white desk
                'media_expansions' => [],
            ],
            [
                'name' => 'Wireless Charger Pad',
                'sku' => 'ELC-007',
                'description' => '10W Qi wireless charging pad. Fast charge for iPhone 14/13/12, Samsung Galaxy.',
                'price' => 12.99,
                'brand' => 'Belkin',
                'main_image' => $p(442),          // tech/charging
                'media_expansions' => [$p(239)],
            ],

            // ── Personal Care ─────────────────────────────────────────────────
            [
                'name' => 'Hand Sanitizer',
                'sku' => 'CARE-001',
                'description' => 'Purell Advanced Hand Sanitizer 1 fl oz. 99.99% effective at killing germs.',
                'price' => 2.99,
                'brand' => 'Purell',
                'main_image' => $p(584),          // clean/health
                'media_expansions' => [],
            ],
            [
                'name' => 'Lip Balm',
                'sku' => 'CARE-002',
                'description' => 'ChapStick Classic Original Lip Balm 0.15 oz. Soothes and moisturizes.',
                'price' => 2.50,
                'brand' => 'ChapStick',
                'main_image' => $p(1027),         // soft/pink tones
                'media_expansions' => [],
            ],
            [
                'name' => 'Sanitizing Wipes',
                'sku' => 'CARE-003',
                'description' => 'Clorox Disinfecting Wipes 15-count. Kills 99.9% of bacteria and viruses.',
                'price' => 3.50,
                'brand' => 'Clorox',
                'main_image' => $p(305),          // clean/white
                'media_expansions' => [],
            ],
            [
                'name' => 'Advil 4-Pack',
                'sku' => 'CARE-004',
                'description' => 'Advil Ibuprofen Tablets 200mg. Fast pain relief for headaches, muscle aches, and more.',
                'price' => 3.99,
                'brand' => 'Advil',
                'main_image' => $p(169),          // pills/health blue
                'media_expansions' => [],
            ],
            [
                'name' => 'Tylenol 4-Pack',
                'sku' => 'CARE-005',
                'description' => 'Tylenol Extra Strength Acetaminophen 500mg. #1 doctor recommended OTC pain reliever.',
                'price' => 3.99,
                'brand' => 'Tylenol',
                'main_image' => $p(214),          // health/white
                'media_expansions' => [],
            ],
            [
                'name' => 'Face Mask 3-Pack',
                'sku' => 'CARE-006',
                'description' => 'Disposable 3-layer face masks. Comfortable ear loops, adjustable nose wire.',
                'price' => 2.99,
                'brand' => 'NIOSH',
                'main_image' => $p(453),          // minimal/white
                'media_expansions' => [],
            ],
            [
                'name' => 'Travel Tissues',
                'sku' => 'CARE-007',
                'description' => 'Puffs Plus Lotion Pocket Pack 10-count. Soft, strong, and gentle on skin.',
                'price' => 1.50,
                'brand' => 'Puffs',
                'main_image' => $p(164),          // soft/pastel
                'media_expansions' => [],
            ],

            // ── Stationery ────────────────────────────────────────────────────
            [
                'name' => 'Notebook',
                'sku' => 'STA-001',
                'description' => 'Moleskine-style pocket notebook 3.5×5.5in, ruled, 192 pages. Perfect for notes on the go.',
                'price' => 5.99,
                'brand' => 'Paperblanks',
                'main_image' => $p(127),          // notebook/desk
                'media_expansions' => [],
            ],
            [
                'name' => 'Pen Set 3-Pack',
                'sku' => 'STA-002',
                'description' => 'BIC Ballpoint Pens medium point, black ink. Smooth writing, reliable performance.',
                'price' => 3.99,
                'brand' => 'BIC',
                'main_image' => $p(20),           // pen/office
                'media_expansions' => [],
            ],
        ];
    }

    public function run(): void
    {
        $this->command->info('🎰 VendingProductSeeder: Creating products and slots…');

        // ── Find or create machine ─────────────────────────────────────────────
        $machine = Machine::query()
            ->where('machine_number', self::MACHINE_NO)
            ->first();

        if ($machine === null) {
            $this->command->warn('Machine '.self::MACHINE_NO.' not found. Please create it first in the admin panel.');
            $this->command->warn('Looking for any available machine…');
            $machine = Machine::query()->first();
        }

        if ($machine === null) {
            $this->command->error('No machine found in database. Cannot seed products.');

            return;
        }

        $this->command->info("Using machine: {$machine->machine_name} ({$machine->machine_number})");

        // ── Create products and slots ─────────────────────────────────────────
        $catalog = $this->catalog();
        $lineNumber = 1;

        foreach ($catalog as $item) {
            $media = $item['media_expansions'] ?? [];

            // Upsert product by SKU
            $product = Product::updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'brand' => $item['brand'],
                    'main_image' => $item['main_image'],
                    'media_expansions' => $media ?: null,
                    'is_active' => true,
                ]
            );

            // Upsert slot
            MachineSlot::updateOrCreate(
                [
                    'machine_id' => $machine->id,
                    'line_number' => $lineNumber,
                ],
                [
                    'product_id' => $product->id,
                    'price' => $item['price'],
                    'max_stock' => 20,
                    'current_stock' => rand(5, 20),
                    'stock_alarm_threshold' => 3,
                    'is_active' => true,
                    'is_fault' => false,
                ]
            );

            $this->command->line("  ✓ [{$lineNumber}] {$item['name']} — \${$item['price']}");
            $lineNumber++;
        }

        $this->command->info("✅ Created {$lineNumber} products and slots.");
    }
}
