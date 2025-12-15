<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CherrypikPage;

class CherrypikPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'template' => 'about_us',
                'description' => 'Learn more about our company and mission.',
                'status' => 1,
                'inner_page_status' => 1,
                'data' => [
                    'title' => 'About Our Company',
                    'description' => 'We are a leading company in our industry.',
                    'feature_1' => 'Professional Service',
                    'feature_2' => 'Expert Team',
                    'feature_3' => 'Quality Results',
                    'feature_4' => '24/7 Support',
                    'feature_5' => 'Affordable Pricing',
                    'feature_6' => 'Fast Delivery',
                    'profile_name' => 'John Doe',
                    'profile_position' => 'CEO & Founder',
                    'contact_number' => '+1 (555) 123-4567'
                ]
            ],
            [
                'title' => 'Our Services',
                'slug' => 'services',
                'template' => 'services',
                'description' => 'Discover our comprehensive range of services.',
                'status' => 1,
                'inner_page_status' => 1,
                'data' => []
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact',
                'template' => 'contacts',
                'description' => 'Get in touch with us for any inquiries.',
                'status' => 1,
                'inner_page_status' => 1,
                'data' => []
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'template' => 'faq',
                'description' => 'Frequently asked questions and answers.',
                'status' => 1,
                'inner_page_status' => 1,
                'data' => []
            ]
        ];

        foreach ($pages as $pageData) {
            CherrypikPage::create($pageData);
        }
    }
}
