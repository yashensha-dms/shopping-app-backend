<?php

namespace Database\Seeders;

use App\Models\HomePage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomePageSeeder extends Seeder
{
    protected $baseURL;

    public function __construct()
    {
        $this->baseURL = config('app.url');
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        $contents = [
            'paris' => [
                'content' => [
                    'home_banner' => [
                        'status' => true,
                        'main_banner' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/paris/1.jpg',
                            'redirect_link' => [
                                'link' =>'',
                                'link_type' => 'collection',
                            ]
                        ],
                        'sub_banner_1' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/paris/2.jpg',
                            'redirect_link' => [
                                'link' =>'',
                                'link_type' => 'collection',
                            ]
                        ],
                        'sub_banner_2' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/paris/2.jpg',
                            'redirect_link' => [
                                'link' =>'',
                                'link_type' => 'collection',
                            ]
                        ]
                    ],

                    'featured_banners' => [
                        'status' => true,
                        'banners' => [
                            [
                                'status'    => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/paris/3.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ],
                            ],
                            [
                                'status'    => true,
                                'image_url' =>$this->baseURL.'/frontend/images/themes/paris/3.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ]
                            ],
                            [
                                'status'    => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/paris/3.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ]
                            ],
                            [
                                'status'    => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/paris/3.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ]
                            ]
                        ],
                    ],

                    'main_content' => [
                        'status'    => true,
                        'sidebar' => [
                            'status' => true,
                            'categories_icon_list' => [
                                'title' => 'Categories',
                                'category_ids' => [],
                                'status'    => true,
                            ],
                            'left_side_banners' => [
                                'status' => true,
                                'banner_1' => [
                                    'image_url' => $this->baseURL.'/frontend/images/themes/paris/4.jpg',
                                    'redirect_link' => [
                                        'link' =>'',
                                        'link_type' => 'collection',
                                    ]
                                ],
                                'banner_2' => [
                                    'image_url' => $this->baseURL.'/frontend/images/themes/paris/5.jpg',
                                    'redirect_link' => [
                                        'link' =>'',
                                        'link_type' => 'collection',
                                    ]
                                ]
                            ],
                            'sidebar_products' => [
                                'title' => 'Trending Products',
                                'status' => true,
                                'product_ids' => []
                            ]
                        ],

                        'section1_products' => [
                            'title' => 'Top Save Today',
                            'description' => "Don't miss this opportunity at a special discount just for this week.",
                           'product_ids' => [],
                           'status' => true
                        ],

                        'section2_categories_list' => [
                            'title' => 'Bowse By Categories',
                            'description' => 'Uncover Hidden Gems and Culinary Delights',
                            'status' => true,
                            'image_url' =>  null,
                            'category_ids' => []
                        ],

                        'section3_two_column_banners' => [
                            'status' => true,
                            'banner_1' => [
                                'image_url' => $this->baseURL.'/frontend/images/themes/paris/6.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ],
                            ],
                            'banner_2' => [
                                'image_url' => $this->baseURL.'/frontend/images/themes/paris/6.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ],
                            ]
                        ],

                        'section4_products' => [
                            'title' => 'Fresh Veggies and Fruits',
                            'description' => "Unlocking the Pantry: A Journey into Essential Food Cupboard Staples",
                            'status' => true,
                            'product_ids' => [],
                        ],

                        'section5_coupons' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/paris/7.jpg',
                            'status' => true,
                            'redirect_link' => [
                                'link' =>'',
                                'link_type' => 'collection',
                            ]
                        ],

                        'section6_two_column_banners' => [
                            'status' => true,
                            'banner_1' => [
                                'image_url' => $this->baseURL.'/frontend/images/themes/paris/8.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ]
                            ],
                            'banner_2' => [
                                'image_url' => $this->baseURL.'/frontend/images/themes/paris/9.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ]
                        ],

                        'section7_products' => [
                            'title' => 'Our Best Seller',
                            'description' => "A virtual assistant collects the products from your list.",
                            'status' => true,
                            'product_ids' => [],
                        ],

                        'section8_full_width_banner' => [
                            'status' => true,
                            'image_url' => $this->baseURL.'/frontend/images/themes/paris/10.jpg',
                            'redirect_link' => [
                                'link' =>'',
                                'link_type' => 'collection',
                            ]
                        ],

                        'section9_featured_blogs' => [
                            'title' => 'Featured Blog',
                            'description' => 'A virtual assistant collects the products from your list',
                            'status' => true,
                            'blog_ids' => [],
                        ]
                    ],

                    'news_letter' => [
                        'title' => 'Join Our Newsletter And Get...',
                        'sub_title' => '$20 discount for your first order',
                        'image_url' => $this->baseURL.'/frontend/images/data/newsletter.jpg',
                        'status' => true,
                    ],

                    'products_ids' => [],
                ]
            ],
            'tokyo' => [
                'content' => [
                    'home_banner' => [
                        'status' => true,
                        'main_banner' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/tokyo/1.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection',
                            ]
                        ],
                        'sub_banner_1' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/tokyo/2.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection',
                            ]
                        ],
                        'sub_banner_2' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/tokyo/2.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection',
                            ]
                        ]
                    ],

                    'categories_icon_list' => [
                        'image_url' =>  $this->baseURL.'/frontend/images/themes/tokyo/3.jpg',
                        'status' => true,
                        'category_ids' => []
                    ],

                    'coupons' => [
                        'status' => true,
                        'image_url' => $this->baseURL.'/frontend/images/themes/tokyo/4.jpg',
                        'redirect_link' => [
                            'link' => '',
                            'link_type' => 'collection'
                        ]
                    ],

                    'featured_banners' => [
                        'status' => true,
                        'banners' => [
                            [
                                'status'    => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/tokyo/5.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                            [
                                'status'    => true,
                                'image_url' =>$this->baseURL.'/frontend/images/themes/tokyo/5.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                            [
                                'status'    => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/tokyo/5.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                            [
                                'status'    => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/tokyo/5.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ]
                        ],
                    ],

                    'main_content' => [
                        'sidebar' => [
                            'status'    => true,
                            'right_side_banners' => [
                                'status'    => true,
                                'banner_1' => [
                                    'image_url' => $this->baseURL.'/frontend/images/themes/tokyo/6.jpg',
                                    'redirect_link' => [
                                        'link' => '',
                                        'link_type' => 'collection'
                                    ]
                                ],
                                'banner_2' => [
                                    'image_url' => $this->baseURL.'/frontend/images/themes/tokyo/6.jpg',
                                    'redirect_link' => [
                                        'link' => '',
                                        'link_type' => 'collection'
                                    ]
                                ]
                            ],
                        ],

                        'section1_products' => [
                            'title' => 'Top Save Today',
                            'status' => true,
                            'product_ids'=> [],
                        ],

                        'section2_slider_products' => [
                            'title' => 'Bakery Delights for Everyone',
                            'status' => true,
                            'product_ids' => [],
                        ],

                        'section3_products' => [
                            'title' => 'Your Daily Staples',
                            'status' => true,
                            'product_ids' => [],
                        ],

                        'section4_products' => [
                            'title' => 'Popular Snakes',
                            'status' => true,
                            'product_ids' => [],
                        ],
                    ],

                    'full_width_banner' => [
                        'image_url' => $this->baseURL.'/frontend/images/themes/tokyo/8.jpg',
                        'status' => true,
                        'redirect_link' => [
                            'link' => '',
                            'link_type' => 'collection',
                        ]
                    ],

                    'slider_products' => [
                        'status' => true,
                        'product_slider_1' => [
                            'title' => 'Top Selling',
                            'status' => true,
                            'product_ids' => [],
                        ],
                        'product_slider_2' => [
                            'title' => 'Trending Products',
                            'status' => true,
                            'product_ids' => [],
                        ],
                        'product_slider_3' => [
                            'title' => 'Recently added',
                            'status' => true,
                            'product_ids' => [],
                        ],
                        'product_slider_4' => [
                            'title' => 'Top Rated',
                            'status' => true,
                            'product_ids' => [],
                        ],
                    ],

                    'news_letter' => [
                        'title' => 'Join Our Newsletter And Get...',
                        'sub_title' => '$20 discount for your first order',
                        'image_url' => $this->baseURL.'/frontend/images/data/newsletter-1.jpg',
                        'status' => true,
                    ],

                    'products_ids' => [],
                ],
            ],
            'osaka' => [
                'content' => [
                    'home_banner' => [
                        'status' => true,
                        'main_banner' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/osaka/1.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection',
                            ]
                        ],
                        'sub_banner_1' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/osaka/2.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection',
                            ]
                        ],
                    ],

                    'categories_icon_list' => [
                        'title' => 'Browse By Categories',
                        'description' => 'Top Categories Of The Week',
                        'category_ids' => [],
                        'image_url' =>  $this->baseURL.'/frontend/images/themes/osaka/3.png',
                        'status' => true,
                    ],

                    'coupons' => [
                        'status' => true,
                        'image_url' => $this->baseURL.'/frontend/images/themes/osaka/4.jpg',
                        'redirect_link' => [
                            'link' => '',
                            'link_type' => 'collection'
                        ]
                    ],

                    'products_list_1' => [
                        'title' => 'Fruits and Vegetables',
                        'description' => "Farm-Fresh Goodness: A Variety of Fruits and Vegetables Awaits",
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'offer_banner' => [
                        'status' => true,
                        'image_url' => $this->baseURL.'/frontend/images/themes/osaka/5.jpg',
                        'redirect_link' => [
                            'link' => '',
                            'link_type' => 'collection'
                        ]
                    ],

                    'products_list_2' => [
                        'title' => 'Breakfast and Dairy',
                        'description' => "Morning Delights: Breakfast and Dairy Choices to Start Your Day",
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'product_bundles' => [
                        'status' => true,
                        'bundles' => [
                            [
                                'title' => 'Hot Deals on New Items',
                                'sub_title' => 'Daily Essentials Eggs & Dairy',
                                'image_url' =>  $this->baseURL.'/frontend/images/themes/osaka/6.jpg',
                                'status' => true,
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                            [
                                'title' => 'Organic Meat Prepared',
                                'sub_title' => 'Delivered to Your Home',
                                'image_url' =>  $this->baseURL.'/frontend/images/themes/osaka/6.jpg',
                                'status' => true,
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                            [
                                'title' => 'Buy More & Save More',
                                'sub_title' => 'Fresh Vegetables & Fruits',
                                'image_url' =>  $this->baseURL.'/frontend/images/themes/osaka/6.jpg',
                                'status' => true,
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                            [
                                'title' => 'Fresh Fruits on Go',
                                'sub_title' => 'Fresh Vegetables & Fruits',
                                'image_url' =>  $this->baseURL.'/frontend/images/themes/osaka/6.jpg',
                                'status' => true,
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                        ]
                    ],

                    'slider_products' => [
                        'status' => true,
                        'product_slider_1' => [
                            'title' => 'Top Selling',
                            'status' => true,
                            'product_ids' => []
                        ],
                        'product_slider_2' => [
                            'title' => 'Trending Products',
                            'status' => true,
                            'product_ids' => []
                        ],
                        'product_slider_3' => [
                            'title' => 'Recently added',
                            'status' => true,
                            'product_ids' => []
                        ],
                        'product_slider_4' => [
                            'title' => 'Top Rated',
                            'status' => true,
                            'product_ids' => []
                        ],
                    ],

                    'featured_blogs' => [
                        'title' => 'Featured Blog',
                        'description' => 'A virtual assistant collects the products from your list',
                        'status' => true,
                        'blog_ids' => [],
                    ],

                    'news_letter' => [
                        'title' => 'Join Our Newsletter And Get...',
                        'sub_title' => '$20 discount for your first order',
                        'image_url' => $this->baseURL.'/frontend/images/data/newsletter.jpg',
                        'status' => true,
                    ],

                    'products_ids' => [],
                ],
            ],
            'rome' => [
                'content' => [
                    'home_banner' => [
                        'status' => true,
                        'bg_image_url' =>  $this->baseURL.'/frontend/images/themes/rome/rome_07.png',
                        'main_banner' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/rome/1.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ],
                        'sub_banner_1' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/rome/2.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ],
                        'sub_banner_2' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/rome/3.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ],
                        'sub_banner_3' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/rome/3.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ],
                    ],

                    'categories_image_list' => [
                        'category_ids' => [],
                        'title' => 'Shop By Categories',
                        'status' => true,
                    ],

                    'value_banners' => [
                        'title' => 'Best Valuable Deals',
                        'status' => true,
                        'banners' => [
                            [
                                'image_url' => $this->baseURL.'/frontend/images/themes/rome/4.jpg',
                                'status' => true,
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection'
                                ]
                            ],
                            [
                                'image_url' => $this->baseURL.'/frontend/images/themes/rome/4.jpg',
                                'status' => true,
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection'
                                ]
                            ],
                            [
                                'image_url' => $this->baseURL.'/frontend/images/themes/rome/4.jpg',
                                'status' => true,
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection'
                                ]
                            ]
                        ],
                    ],

                    'value_banners' => [
                        'title' => 'Best Valuable Deals',
                        'status' => true,
                        'banners' => [
                            [
                                'status' => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/rome/4.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection'
                                ]
                            ],
                            [
                                'status' => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/rome/4.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection'
                                ]
                            ],
                            [
                                'status' => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/rome/4.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection'
                                ]
                            ],
                        ]
                    ],

                    'categories_products' => [
                        'title' => 'Our Products',
                        'status' => true,
                        'category_ids' => []
                    ],

                    'two_column_banners' => [
                        'status' => true,
                        'banner_1' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/rome/5.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ],
                        'banner_2' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/rome/5.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ]
                    ],

                    'slider_products' => [
                        'status' => true,
                        'product_slider_1' => [
                            'title' => 'New Products',
                            'status' => true,
                            'product_ids' => [],
                        ],
                        'product_slider_2' => [
                            'title' => 'Featured Products',
                            'status' => true,
                            'product_ids' => [],
                        ],
                        'product_slider_3' => [
                            'title' => 'Best Seller',
                            'status' => true,
                            'product_ids' => [],
                        ],
                        'product_slider_4' => [
                            'title' => 'Trending Products',
                            'status' => true,
                            'product_ids' => [],
                        ],
                    ],

                    'full_width_banner' => [
                        'image_url' => $this->baseURL.'/frontend/images/themes/rome/7.jpg',
                        'status' => true,
                        'redirect_link' => [
                            'link' => '',
                            'link_type' => 'collection'
                        ]
                    ],

                    'products_list_1' => [
                        'title' => 'Top Products',
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'featured_blogs' => [
                        'title' => 'Featured Blog',
                        'status' => true,
                        'blog_ids' => [],
                    ],

                    'news_letter' => [
                        'title' => 'Subscribe to the newsletter',
                        'sub_title' => 'Join our subscribers list to get the latest news, updates and special offers
                        delivered directly in your inbox.',
                        'image_url' =>  $this->baseURL.'/frontend/images/data/newsletter-2.jpg',
                        'status' => true,
                    ],

                    'products_ids' => [],
                ],
            ],
            'madrid' => [
                'content' => [
                    'home_banner' => [
                        'status' => true,
                        'main_banner' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/madrid/1.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ],
                    ],

                    'featured_banners' => [
                        'status' => true,
                        'banners' => [
                            [
                                'status' => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/madrid/2.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                            [
                                'status' => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/madrid/2.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                            [
                                'status' => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/madrid/2.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                            [
                                'status' => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/madrid/2.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                        ],
                    ],

                    'categories_image_list' => [
                        'title' => 'Shop By Categories',
                        'status' => true,
                        'category_ids' => []
                    ],

                    'products_list_1' => [
                        'title' => 'Fruits & Vegetables',
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'bank_wallet_offers' => [
                        'title' => 'Bank & Wallet Offers',
                        'status' => true,
                        'offers' => [
                            [
                                'image_url' => $this->baseURL.'/frontend/images/themes/madrid/3.jpg',
                                'coupon_code' => "FASTPR10",
                                'status' => true,
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                            [
                                'image_url' => $this->baseURL.'/frontend/images/themes/madrid/3.jpg',
                                'coupon_code' => "FASTBOGO",
                                'status' => true,
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                            [
                                'image_url' => $this->baseURL.'/frontend/images/themes/madrid/3.jpg',
                                'coupon_code' => "FASTFESTIVE",
                                'status' => true,
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ],
                        ]
                    ],

                    'product_with_deals' => [
                        'title' => 'Top Selling Items',
                        'status' => true,
                        'products_list' => [
                            'title' => 'Top Selling Items',
                            'status' => true,
                            'product_ids' => [],
                        ],

                        'deal_of_days' => [
                            'title' => 'Special Offer',
                            'status' => true,
                            'image_url' =>  $this->baseURL.'/frontend/images/themes/madrid/4.jpg',
                            'deals' => []
                        ],

                    ],

                    'full_width_banner' => [
                        'image_url' => $this->baseURL.'/frontend/images/themes/madrid/5.jpg',
                        'status' => true,
                        'full_width_banner' => []
                    ],

                    'products_list_2' => [
                        'title' => 'Breakfast & Dairy',
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'products_list_3' => [
                        'title' => 'Fresh Fruits',
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'two_column_banners' => [
                        'status' => true,
                        'banner_1' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/madrid/7.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection',
                                'product_ids' => null
                            ]
                        ],
                        'banner_2' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/madrid/8.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection',
                                'product_ids' => null
                            ]
                        ]
                    ],

                    'products_list_4' => [
                        'title' => 'Organic Vegetables',
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'products_list_5' => [
                        'title' => 'Our Best Sellers',
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'delivery_banners' => [
                        'status' => true,
                        'banner_1' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/madrid/7.jpg',
                            'redirecrt_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ],
                        'banner_2' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/madrid/8.jpg',
                            'redirecrt_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ]
                    ],

                    'products_list_6' => [
                        'title' => 'Personal Care',
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'products_list_7' => [
                        'title' => 'New Arrivals',
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'featured_blogs' => [
                        'title' => 'Featured Blog',
                        'status' => true,
                        'blog_ids' => [],
                    ],

                    'products_ids' => [],

                ],
            ],
            'berlin' => [
                'content' => [
                    'home_banner' => [
                        'status' => true,
                        'main_banner' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/berlin/1.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]

                        ],
                        'sub_banner_1' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/berlin/2.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ],

                    ],

                    'services_banner' => [
                        'status' => true,
                        'services' => [
                            [
                                'title' => 'Free Shipping',
                                'sub_title' => 'Free Shipping world wide',
                                'status' => true,
                                'image_url' =>  $this->baseURL.'/frontend/images/themes/berlin/7.png'
                            ],
                            [
                                'title' => '24 x 7 Service',
                                'sub_title' => 'Online Service For 24 x 7',
                                'status' => true,
                                'image_url' =>  $this->baseURL.'/frontend/images/themes/berlin/8.png'
                            ],
                            [
                                'title' => 'Online Pay',
                                'sub_title' => 'Online Payment Available',
                                'status' => true,
                                'image_url' =>  $this->baseURL.'/frontend/images/themes/berlin/9.png'
                            ],
                            [
                                'title' => 'Festival Offer',
                                'sub_title' => 'Super Sale Upto 50% off',
                                'status' => true,
                                'image_url' =>  $this->baseURL.'/frontend/images/themes/berlin/10.png'
                            ],
                            [
                                'title' => '100% Original',
                                'sub_title' => '100% Money Back',
                                'status' => true,
                                'image_url' =>  $this->baseURL.'/frontend/images/themes/berlin/11.png'
                            ]
                        ]
                    ],

                    'main_content' => [
                        'status' => true,
                        'sidebar' => [
                            'status' => true,
                            'categories_icon_list' => [
                                'title' => 'Shop By Product',
                                'category_ids' => [],
                                'status' => true,
                            ],
                            'right_side_banners' => [
                               'status' => true,
                                'banner_1' => [
                                    'image_url' => $this->baseURL.'/frontend/images/themes/berlin/3.jpg',
                                    'redirect_link' => [
                                        'link' => '',
                                        'link_type' => 'collection'
                                    ]
                                ],
                            ],
                            'sidebar_products' => [
                                'title' => 'Trending Products',
                                'status' => true,
                                'product_ids' => [],
                            ]
                        ],

                        'section1_products' => [
                            'title' => 'Top Save Today',
                            'description' => "Don't miss this opportunity at a special discount just for this week.",
                            'status' => true,
                            'product_ids' => [],
                        ],

                        'section2_categories_icon_list' => [
                            'title' => 'Categories',
                            'description' => 'Top Categories Of The Week',
                            'image_url' =>  $this->baseURL.'/frontend/images/themes/berlin/12.png',
                            'status' => true,
                            'category_ids' => []
                        ],

                        'section3_two_column_banners' => [
                            'status' => true,
                            'banner_1' => [
                                'image_url' => $this->baseURL.'/frontend/images/themes/berlin/4.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection'
                                ]
                            ],
                            'banner_2' => [
                                'link' => null,
                                'image_url' => $this->baseURL.'/frontend/images/themes/berlin/4.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection'
                                ]
                            ]
                        ],

                        'section4_products' => [
                            'title' => 'Elegant Designs',
                            'description' => "Crafting timeless, sophisticated furniture for your dream living spaces.",
                            'status' => true,
                            'product_ids' => [],
                        ],
                    ],

                    'full_width_banner' => [
                        'image_url' => $this->baseURL.'/frontend/images/themes/berlin/5.jpg',
                        'redirect_link' => [
                            'link' => '',
                            'link_type' => 'collection'
                        ]
                    ],

                    'product_list_1' => [
                        'title' => 'Furniture Collections',
                        'description' => "Contemporary designs for stylish, comfortable living spaces.",
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'news_letter' => [
                        'title' => 'Join Our Newsletter And Get...',
                        'sub_title' => '$20 discount for your first order',
                        'image_url' => $this->baseURL.'/frontend/images/data/newsletter.jpg',
                        'status' => true,
                    ],

                    'products_ids' => [],
                ],
            ],
            'denver' => [
                'content' => [
                    'home_banner' => [
                        'status' => true,
                        'main_banner' => [
                            'link' => null,
                            'image_url' => $this->baseURL.'/frontend/images/themes/denver/1.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ],
                    ],

                    'categories_icon_list' => [
                        'category_ids' => [],
                        'image_url' =>  $this->baseURL.'/frontend/images/themes/denver/6.png',
                        'status' => true,
                    ],

                    'products_list_1' => [
                        'title' => 'Top Selling Items',
                        'product_ids' => [],
                        'status' => true,
                    ],

                    'two_column_banners' => [
                        'status' => true,
                        'banner_1' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/denver/2.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ],
                        'banner_2' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/denver/3.jpg',
                            'redirect_link' => [
                                'link' => '',
                                'link_type' => 'collection'
                            ]
                        ]
                    ],

                    'slider_product_with_banner' => [
                        'left_side_banners' => [
                           'status' => true,
                            'banner_1' => [
                                'status' => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/denver/4.jpg',
                                'redirect_link' => [
                                    'link' => '',
                                    'link_type' => 'collection'
                                ]
                            ]
                        ],

                        'slider_products' => [
                            'status' => true,
                            'product_slider_1' => [
                                'title' => 'Top Selling',
                                'product_ids' => [],
                                'status' => true,
                            ],
                            'product_slider_2' => [
                                'title' => 'Trending Products',
                                'product_ids' => [],
                                'status' => true,
                            ],
                            'product_slider_3' => [
                                'title' => 'Recently added',
                                'product_ids' => [],
                                'status' => true,
                            ],
                        ],
                    ],

                    'coupon_banner' => [
                        'link' => null,
                        'image_url' => $this->baseURL.'/frontend/images/themes/denver/5.jpg',
                        'redirect_link' => [
                            'link' => '',
                            'link_type' => 'collection'
                        ]
                    ],

                    'products_list_2' => [
                        'title' => 'Trendy Fashion Finds',
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'products_list_3' => [
                        'title' => 'Chic Style Selection',
                        'status' => true,
                        'product_ids' => [],
                    ],

                    'news_letter' => [
                        'title' => 'Join Our Newsletter And Get...',
                        'sub_title' => '$20 discount for your first order',
                        'image_url' => $this->baseURL.'/frontend/images/data/newsletter.jpg',
                        'status' => true,
                    ],

                    'products_ids' => [],
                ],
            ],
        ];

        foreach($contents as $slug => $data) {
            HomePage::updateOrCreate([
                'slug' => $slug,
                'content' => $data['content'],
            ]);
        }

        DB::table('seeders')->updateOrInsert([
            'name' => 'HomePageSeeder',
            'is_completed' => true
        ]);
    }
}
