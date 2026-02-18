<?php

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\IndustrySkill;
use App\Models\IndustryTemplate;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $industries = [
            [
                'name' => 'Coffee Shop',
                'slug' => 'coffee-shop',
                'sort_order' => 1,
                'skills' => [
                    'Barista', 'Espresso', 'Latte Art', 'Pour Over', 'Cold Brew',
                    'Customer Service', 'Cash Handling', 'Opening', 'Closing', 'Cleaning',
                ],
                'templates' => [
                    [
                        'title' => 'Morning Barista Needed',
                        'description' => 'Looking for an experienced barista for the morning rush. Must be comfortable on espresso machines and providing fast, friendly service.',
                        'skills' => ['Barista', 'Espresso', 'Customer Service', 'Opening'],
                        'sort_order' => 1,
                    ],
                    [
                        'title' => 'Weekend Barista — Busy Shift',
                        'description' => 'High-volume weekend shift. Must handle espresso bar, pour-overs, and cold brew efficiently under pressure.',
                        'skills' => ['Barista', 'Espresso', 'Pour Over', 'Cold Brew', 'Cash Handling'],
                        'sort_order' => 2,
                    ],
                    [
                        'title' => 'Closing Shift — Barista',
                        'description' => 'Responsible for closing duties including cleaning, restocking, and end-of-day cash reconciliation.',
                        'skills' => ['Barista', 'Closing', 'Cleaning', 'Cash Handling'],
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'name' => 'Restaurant',
                'slug' => 'restaurant',
                'sort_order' => 2,
                'skills' => [
                    'Line Cook', 'Prep Cook', 'Dishwasher', 'Server', 'Host',
                    'Food Prep', 'Food Safety', 'Bussing',
                ],
                'templates' => [
                    [
                        'title' => 'Line Cook — Dinner Service',
                        'description' => 'Experienced line cook needed for dinner service. Must know station management and work cleanly under pressure.',
                        'skills' => ['Line Cook', 'Food Safety', 'Food Prep'],
                        'sort_order' => 1,
                    ],
                    [
                        'title' => 'Server — Weekend Brunch',
                        'description' => 'Friendly, experienced server for busy weekend brunch service. Must handle high table counts and POS system.',
                        'skills' => ['Server', 'Customer Service', 'Food Safety'],
                        'sort_order' => 2,
                    ],
                    [
                        'title' => 'Prep Cook — Morning Shift',
                        'description' => 'Early-morning prep cook to get mise en place ready before service. Attention to detail and food safety knowledge required.',
                        'skills' => ['Prep Cook', 'Food Prep', 'Food Safety'],
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'name' => 'Salon',
                'slug' => 'salon',
                'sort_order' => 3,
                'skills' => [
                    'Haircuts', 'Hair Coloring', 'Highlights', 'Blow Dry',
                    'Waxing', 'Nail Tech', 'Customer Service',
                ],
                'templates' => [
                    [
                        'title' => 'Stylist — Full Day Coverage',
                        'description' => 'Licensed stylist needed to cover a full day of appointments. Must perform cuts, color, and blow-outs independently.',
                        'skills' => ['Haircuts', 'Hair Coloring', 'Blow Dry', 'Customer Service'],
                        'sort_order' => 1,
                    ],
                    [
                        'title' => 'Color Specialist Needed',
                        'description' => 'Experienced colorist for a busy Saturday. Must be proficient in balayage, highlights, and single-process color.',
                        'skills' => ['Hair Coloring', 'Highlights', 'Customer Service'],
                        'sort_order' => 2,
                    ],
                ],
            ],
            [
                'name' => 'Retail',
                'slug' => 'retail',
                'sort_order' => 4,
                'skills' => [
                    'Cashier', 'Stocking', 'Inventory', 'Customer Service',
                    'Visual Merchandising', 'POS Systems',
                ],
                'templates' => [
                    [
                        'title' => 'Retail Associate — Floor Coverage',
                        'description' => 'Customer-facing retail associate needed for floor coverage. Responsible for assisting customers, restocking shelves, and maintaining store appearance.',
                        'skills' => ['Customer Service', 'Stocking', 'POS Systems'],
                        'sort_order' => 1,
                    ],
                    [
                        'title' => 'Cashier — Holiday Rush',
                        'description' => 'High-volume cashier position during peak season. Must handle fast transactions and maintain a positive attitude with customers.',
                        'skills' => ['Cashier', 'POS Systems', 'Customer Service'],
                        'sort_order' => 2,
                    ],
                    [
                        'title' => 'Stocking & Inventory',
                        'description' => 'Overnight or early-morning stocking shift. Receiving shipments, organizing back stock, and updating inventory counts.',
                        'skills' => ['Stocking', 'Inventory', 'Visual Merchandising'],
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'name' => 'Automotive',
                'slug' => 'automotive',
                'sort_order' => 5,
                'skills' => [
                    'Oil Change', 'Tire Rotation', 'Brake Service', 'Detailing',
                    'Customer Service', 'Alignment',
                ],
                'templates' => [
                    [
                        'title' => 'Quick Lube Technician',
                        'description' => 'Experienced technician for oil changes, filter replacements, and fluid checks. Must work efficiently and safely.',
                        'skills' => ['Oil Change', 'Tire Rotation', 'Customer Service'],
                        'sort_order' => 1,
                    ],
                    [
                        'title' => 'Auto Detailer — Weekend',
                        'description' => 'Professional detailer for interior and exterior car detailing. Attention to detail required.',
                        'skills' => ['Detailing', 'Customer Service'],
                        'sort_order' => 2,
                    ],
                ],
            ],
            [
                'name' => 'Healthcare',
                'slug' => 'healthcare',
                'sort_order' => 6,
                'skills' => [
                    'Patient Care', 'Scheduling', 'Medical Records', 'EMR Systems', 'Customer Service',
                ],
                'templates' => [
                    [
                        'title' => 'Front Desk — Medical Office',
                        'description' => 'Patient check-in/check-out, scheduling, and insurance verification. Must be professional and organized.',
                        'skills' => ['Scheduling', 'Customer Service', 'Medical Records'],
                        'sort_order' => 1,
                    ],
                    [
                        'title' => 'Medical Records Clerk',
                        'description' => 'Organizing and digitizing patient records. Proficiency with EMR systems required. Strict confidentiality expected.',
                        'skills' => ['Medical Records', 'EMR Systems'],
                        'sort_order' => 2,
                    ],
                ],
            ],
        ];

        foreach ($industries as $industryData) {
            $skills = $industryData['skills'];
            $templates = $industryData['templates'];
            unset($industryData['skills'], $industryData['templates']);

            $industry = Industry::create([
                ...$industryData,
                'is_active' => true,
            ]);

            foreach ($skills as $i => $skillName) {
                IndustrySkill::create([
                    'industry_id' => $industry->id,
                    'name' => $skillName,
                    'sort_order' => $i,
                ]);
            }

            foreach ($templates as $templateData) {
                IndustryTemplate::create([
                    'industry_id' => $industry->id,
                    ...$templateData,
                ]);
            }
        }
    }
}
