<?php

namespace Database\Seeders;

use App\Helpers\Helpers;
use App\Models\ThemeOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemeOptionSeeder extends Seeder
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
    $options = [
      'general' => [
        'site_title' => 'FastKart Marketplace: Where Vendors Shine Together',
        'site_tagline' => "Shop Unique, Sell Exceptional – FastKart's Multi-Vendor Universe.",
        'sticky_cart_enable' => true,
        'cart_style' => 'cart_sidebar',
        'back_to_top_enable' => true,
        'language_direction' => 'ltr',
        'primary_color' => '#0da487',
        'mode' => 'light',
        'seller_register_url' => '',
      ],
      'logo' => [
        'header_logo_id' =>  Helpers::getAttachmentId('logo-dark.png'),
        'footer_logo_id' =>  Helpers::getAttachmentId('logo-dark.png'),
        'favicon_icon_id' => Helpers::getAttachmentId('favicon.png'),
      ],
      'header' => [
        'sticky_header_enable' => true,
        'header_options' => 'basic_header',
        'page_top_bar_enable' => true,
        'top_bar_content' => [
          [
              "content" => "<strong class=\"me-1\">Welcome to Fastkart!</strong>Wrap new offers/gift every single day on Weekends.<strong class=\"ms-1\">New Coupon Code: FAST50</strong>"
          ],
          [
              "content" =>  "Something you love is now on sale <strong>Buy Now!</strong>"
          ],
          [
              "content" =>  "Your must-have item is calling – <strong>Buy Now!</strong>"
          ]
        ],
        'page_top_bar_dark' => false,
        'support_number' => '+1-555-186-5359',
        'today_deals' => [],
        'category_ids' => [],
      ],
      'footer' => [
        'footer_style' => 'light_mode',
        'footer_copyright' => true,
        'copyright_content' => '©2023 Fastkart All rights reserved',
        "footer_about" => "Discover convenience redefined at our multipurpose store. From fresh groceries to the latest fashion trends, find everything you need under one roof. Your one-stop shopping destination for a diverse range of products.",
        "about_address" => "1418 Riverwood Drive, CA 96052, US",
        'about_email' => 'support@fastkart.com',
        'footer_categories' => [],
        'useful_link' => [],
        'help_center' => [],
        "support_number" => "+1-555-186-5359",
        'support_email' => 'support@fastkart.com',
        'play_store_url' => 'https://google.com/',
        'app_store_url' => 'https://apple.com/',
        'social_media_enable' => true,
        'facebook' => 'https://facebook.com/',
        'instagram' => 'https://instagram.com/',
        'twitter' => 'https://twitter.com/',
        'pinterest' => 'https://pinterest.com/',
      ],
      'collection' => [
        'collection_layout' => 'collection_category_slider',
        'collection_banner_image_url' => null,
      ],
      'product' => [
        'product_layout' => 'product_thumbnail',
        'is_trending_product' => true,
        'banner_enable' => true,
        'banner_image_url' => null,
        'safe_checkout' => true,
        'safe_checkout_image' => $this->baseURL.'/frontend/images/data/payments.png',
        'secure_checkout' => true,
        'secure_checkout_image' => $this->baseURL.'/frontend/images/data/secure_checkout.png',
        'encourage_order' => true,
        'encourage_max_order_count' => 50,
        'encourage_view' => true,
        'encourage_max_view_count' => 50,
        'sticky_checkout' => true,
        'sticky_product' => true,
        'social_share' => true,
        'shipping_and_return' => "<p>Shipping and Returns are integral parts of your shopping experience, and we aim to make them as smooth as possible. We prioritize efficient shipping, striving to deliver your orders promptly within the estimated delivery window, typically ranging from 5 to 7 days. We understand that sometimes your purchase may not meet your expectations, so we offer a straightforward return policy. If you find yourself unsatisfied with your order, eligible items can be returned within 30 days of purchase, ensuring you have ample time to make a decision. Our commitment is to ensure your satisfaction and convenience throughout your shopping journey with us, and we're here to assist you every step of the way.</p><p><strong>Our Shipping Commitment:</strong></p><ul><li>Timely and reliable delivery within 5-7 days.</li><li>Real-time tracking for your orders.</li><li>Exceptional packaging to ensure your items arrive in perfect condition.</li></ul><p>&nbsp;</p><p><strong>Our Hassle-Free Returns:</strong></p><ul><li>Eligible items can be returned within 30 days.</li><li>Easy return initiation through our website.</li><li>Prompt processing of returns for a hassle-free experience.</li></ul><p>&nbsp;</p><p>We understand that your shopping needs may vary, and we are here to accommodate them while providing exceptional service.</p>"
      ],
      'blog' => [
        'blog_style' => 'grid_view',
        'blog_sidebar_type' => 'left_sidebar',
        'blog_author_enable' => true,
        'read_more_enable' => true,
      ],
      'seller' => [
        'about' => [
          'status' => true,
          'title' => 'BECOME A SELLER ON FASTKART..',
          "description" => "Ready to showcase your products to the world? Join our dynamic marketplace and become a seller at our thriving multipurpose store. With a diverse customer base and a wide range of categories including groceries, fashion, electronics, and more, you'll have the perfect platform to reach a vast audience.\n\nAs a seller, you'll benefit from our user-friendly interface, seamless payment processing, and dedicated support to ensure your products shine. Whether you're a local artisan or a growing brand, our store provides the visibility and tools you need to succeed.\n\nTap into our established customer traffic, set up your shop with ease, and let your products take center stage. Join us in creating a shopping experience that caters to every need and taste. Your journey to success starts here – become a seller at our multipurpose store today!",
          'image_url' => $this->baseURL.'/frontend/images/data/become-seller.png'
        ],
        'services' => [
          'status' => true,
          'title' => "WHY SELL ON FASTKART ?",
          'service_1' => [
            'title' => 'Lowest Cost',
            'description' => "Unlock quality at the lowest cost, exceeding expectations.",
            'image_url' =>  $this->baseURL.'/frontend/images/data/services/1.png',
          ],
          'service_2' => [
            'title' => 'Lowest Cost',
            'description' => "Unlock quality at the lowest cost, exceeding expectations.",
            'image_url' => $this->baseURL.'/frontend/images/data/services/2.png',
          ],
          'service_3' => [
            'title' => 'Dedicated Pickup',
            "description" => "Enjoy the convenience of dedicated pickup services for your orders.",
            'image_url' => $this->baseURL.'/frontend/images/data/services/3.png',
          ],
          'service_4' => [
            'title' => 'Most Approachable',
            "description" => "We take pride in being the most approachable choice for your needs.",
            'image_url' => $this->baseURL.'/frontend/images/data/services/4.png',
          ]
        ],
        'steps' =>  [
          'status' => true,
          "title" => "Doing Business On Fastkart Is Really Easy",
          'step_1' => [
            'title' => "List Your Products & Get Support Service Provider",
            'description' => "Elevate your business by listing your products with us. Experience dedicated support services for your growth."
          ],
          'step_2' => [
            'title' => "Receive orders & Schedule a pickup",
            'description' => "Effortlessly receive orders and schedule pickups for ultimate convenience. Your business is simplified."
          ],
          'step_3' => [
            'title' => "Receive quick payment & grow your business",
            'description' => "Receive swift payments, fuel the growth of your business seamlessly, and watch your ventures thrive."
          ],
        ],
        'start_selling' => [
          'status' => true,
          'title' => "Start Selling",
          'description' => "Fastkart marketplace is India's leading platform for selling online. Be it a manufacturer, vendor or supplier, simply sell your products online on Fastkart and become a top ecommerce player with minimum investment. Through a team of experts offering exclusive seller workshops, training, seller support and convenient seller portal, Fastkart focuses on educating and empowering sellers across India. Selling on Fastkart.com is easy and absolutely free. All you need is to register, list your catalogue and start selling your products."
        ],
        "store_layout" =>  "basic_store",
        "store_details" => "basic_store_details"
      ],
      'contact_us' => [
        'contact_image_url' => $this->baseURL . '/frontend/images/data/contact-us.png',
        'detail_1' => [
          "label" => "Phone",
          "icon" =>  "ri-phone-line",
          "text" => "(+1) 618 190 496"
        ],
        'detail_2' => [
          "label" => "Email",
          "icon" =>  "ri-mail-line",
          "text" => "support@fastkart.com"
        ],
        'detail_3' => [
          "label" => "London Office",
          "icon" =>  "ri-map-pin-line",
          "text" => "Cruce Casa de Postas 29"
        ],
        'detail_4' => [
          "label" => "Bournemouth Office",
          "icon" =>  "ri-building-line",
          "text" => "Visitación de la Encina 22"
        ]
      ],
      'about_us' => [
        "about" => [
          'status' => true,
          "content_left_image_url" =>  "$this->baseURL/frontend/images/data/about_banner.png",
          "content_right_image_url" =>  "$this->baseURL/frontend/images/data/about_banner.png",
          "sub_title" => "About Us",
          "title" => "We Make Organic Food In Market",
          "description" => "Just a few seconds to measure your body temperature. Up to 5 users! The battery lasts up to 2 years. There are many variations of passages of Lorem Ipsum available.We started in 2019 and haven't stopped smashing it since. A global brand that doesn't sleep, we are 24/7 and always bringing something new with over 100 new products dropping on the monhtly, bringing you the latest looks for less.",
          "futures" => [
            [
              "icon" => "$this->baseURL/frontend/images/data/delivery.svg",
              "title" => "Free delivery for all orders"
            ],
            [
              "icon" => "$this->baseURL/frontend/images/data/leaf.svg",
              "title" => "Only fresh foods"
            ],
          ]
        ],
        "clients" => [
          "status" => true,
          "sub_title" => "What We Do",
          "title" => "We Are Trusted By Clients",
          "content" => [
            [
              "icon" => "$this->baseURL/frontend/images/data/user.svg",
              "title" => "Happy Customers",
              "description" => "My goal for this coffee shop is to be able to get a coffee and get on with my day. It's a Thursday morning and I am rushing between meetings."
            ],
            [
              "icon" => "$this->baseURL/frontend/images/data/work.svg",
              "title" => "Business Years",
              "description" => "A coffee shop is a small business that sells coffee, pastries, and other morning goods. There are many different types of coffee shops around the world."
            ],
            [
              "icon" => "$this->baseURL/frontend/images/data/buy.svg",
              "title" => "Products Sales",
              "description" => "Some coffee shops have a seating area, while some just have a spot to order and then go somewhere else to sit down. The coffee shop that I am going to."
            ]
          ]
        ],
        "team" => [
          "status" => true,
          "sub_title" => "Our Creative Team",
          "title" => "Fastkart Team Member",
          "members" => [
            [
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Betty J. Turner",
              "designation" => "CEO, Company",
              "description" => "Fondue stinking bishop goat. Macaroni cheese croque monsieur cottage cheese.",
              "instagram" => "https://instagram.com/",
              "twitter" => "https://twitter.com/",
              "pinterest" => "https://pinterest.com/",
              "facebook" =>  "https://www.facebook.com/"

            ],
            [
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Alfredo S. Rocha",
              "designation" => "Sr. Project Manager",
              "description" => "camembert de normandie. Bocconcini rubber cheese fromage frais port-salut.",
              "instagram" => "https://instagram.com/",
              "twitter" => "https://twitter.com/",
              "pinterest" => "https://pinterest.com/",
              "facebook" =>  "https://www.facebook.com/"
            ],
            [
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Constance K. Whang",
              "designation" => "Jr. Project Manager",
              "description" => "camembert de normandie. Bocconcini rubber cheese fromage frais port-salut.",
              "instagram" => "https://instagram.com/",
              "twitter" => "https://twitter.com/",
              "pinterest" => "https://pinterest.com/",
              "facebook" =>  "https://www.facebook.com/"
            ],
            [
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Gwen J. Geiger",
              "designation" => "Designer",
              "description" => "cheese on toast mozzarella bavarian bergkase smelly cheese cheesy feet",
              "instagram" => "https://instagram.com/",
              "twitter" => "https://twitter.com/",
              "pinterest" => "https://pinterest.com/",
              "facebook" =>  "https://www.facebook.com/"
            ],
          ]
        ],
        "testimonial" => [
          "status" => true,
          "sub_title" => "Latest Testimonials",
          "title" => "What People Say",
          "reviews" => [
            [
              "title" => "Disappointing Experience",
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Betty J. Turner",
              "review" => "I had high hopes for this product, but it fell short of my expectations. It constantly crashes and lacks essential features. I wouldn't recommend it.",
              "designation" => "CEO, Company"
            ],
            [
              "title" => "Disappointing Experience",
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Alfredo S. Rocha",
              "review" => "I bought this product on a budget, and it exceeded my expectations. The quality is impressive for the price. I'm delighted with my purchase.",
              "designation" => "Sr. Project Manager"
            ],
            [
              "title" => "Top Quality, Beautiful Location",
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Constance K. Whang",
              "review" => "I usually try to keep my sadness pent up inside where it can fester quietly as a mental illness. There, now he's trapped in a book I wrote: a crummy world of plot holes and spelling errors! As an interesting side note.",
              "designation" => "Jr. Project Manager"
            ],
            [
              "title" => "Excellent Customer Service",
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Gwen J. Geiger",
              "review" => "I encountered a minor issue with my purchase, and the customer service team was quick to resolve it. Their prompt and friendly support made me a happy customer!",
              "designation" => "Designer"
            ]
          ]
        ],
        "blog" => [
          "status" => true,
          "blog_ids" => []
        ]
      ],
      'error_page' => [
        "error_page_content" => "The page you are looking for could not be found. The link to this address may be outdated or we may have moved the since you last bookmarked it.",
        'back_button_enable' => true,
        'back_button_text' => "Back To Home",
      ],
      'seo' => [
        'meta_tags' => 'FastKart Marketplace: Where Vendors Shine Together',
        "meta_title" => "Online Marketplace, Vendor Collaboration, E-commerce Platform",
        "meta_description" => "Discover FastKart Marketplace – a vibrant online platform where vendors unite to showcase their products, creating a diverse shopping experience. Explore a wide range of offerings and connect with sellers on a single platform.",
        "og_title" => "FastKart Marketplace: Uniting Vendors for Shopping Excellence",
        "og_description" => "Experience a unique shopping journey at FastKart Marketplace, where vendors collaborate to provide a vast array of products. Explore, shop, and connect in one convenient destination.",
        'og_image_id' => null
      ],
    ];

    ThemeOption::updateOrCreate(['options' => $options]);
    DB::table('seeders')->updateOrInsert([
      'name' => 'ThemeOptionSeeder',
      'is_completed' => true
    ]);
  }
}
