<?php
/**
 * VenuePro – Standalone Demo Data Regeneration Script
 * 
 * Usage:
 *  - CLI:     php database/seed_demos.php
 *  - Browser: http://localhost/venuepro/database/seed_demos.php
 * 
 * This script wipes all application data and populates clean, realistic,
 * relational demonstration data across all 15 tables for all 4 roles.
 */

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "============================================================\n";
echo "🏛️  VENUEPRO DEMO DATA REGENERATION UTILITY\n";
echo "============================================================\n\n";

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    echo "[1/16] Connected to database 'venuepro' successfully.\n";
} catch (Exception $e) {
    die("❌ Fatal Error: Could not connect to database: " . $e->getMessage() . "\n");
}

// -------------------------------------------------------------
// Step 1: Wipe all existing tables in reverse dependency order
// -------------------------------------------------------------
echo "[2/16] Purging all existing table records...\n";
$db->exec("SET FOREIGN_KEY_CHECKS = 0;");

$tables = [
    'booking_singular_items',
    'invoices',
    'caterer_orders',
    'staff_assignments',
    'chat_messages',
    'notifications',
    'bookings',
    'package_menu_items',
    'catering_packages',
    'singular_menu_items',
    'venues',
    'caterer_profiles',
    'staff_profiles',
    'site_policies',
    'users'
];

foreach ($tables as $tbl) {
    $db->exec("TRUNCATE TABLE `$tbl`;");
}
$db->exec("SET FOREIGN_KEY_CHECKS = 1;");
echo "       ✔ All 15 tables truncated and reset.\n\n";

$passHash = password_hash('password123', PASSWORD_BCRYPT);

// -------------------------------------------------------------
// Step 2: Insert Users (4 Core Roles + Additional Staff)
// -------------------------------------------------------------
echo "[3/16] Seeding Users...\n";
$usersSql = "INSERT INTO users (id, name, email, password_hash, role, phone, avatar_text, avatar_bg, status) VALUES
(1, 'Mahmud Rahman', 'customer@venuepro.com', ?, 'customer', '+1 (555) 234-5678', 'MR', '#2563eb', 'active'),
(2, 'Alex Rivera', 'caterer@venuepro.com', ?, 'caterer', '+1 (555) 345-6789', 'AR', '#059669', 'active'),
(3, 'Sarah Jenkins', 'staff@venuepro.com', ?, 'staff', '+1 (555) 456-7890', 'SJ', '#7c3aed', 'active'),
(4, 'Alex Sterling', 'admin@venuepro.com', ?, 'admin', '+1 (555) 987-6543', 'AS', '#0f172a', 'active'),
(5, 'Marcus Vance', 'marcus.staff@venuepro.com', ?, 'staff', '+1 (555) 567-8901', 'MV', '#0284c7', 'active'),
(6, 'Elena Rostova', 'elena.staff@venuepro.com', ?, 'staff', '+1 (555) 678-9012', 'ER', '#db2777', 'active');";

$stmtUsers = $db->prepare($usersSql);
$stmtUsers->execute([$passHash, $passHash, $passHash, $passHash, $passHash, $passHash]);
echo "       ✔ Inserted 6 users (1 customer, 1 caterer, 3 staff, 1 admin).\n";

// -------------------------------------------------------------
// Step 3: Insert Caterer & Staff Profiles
// -------------------------------------------------------------
echo "[4/16] Seeding Caterer and Staff Profiles...\n";
$db->exec("INSERT INTO caterer_profiles (user_id, business_name, owner_name, kitchen_address, specialization, rating, review_count, approval_status) VALUES
(2, 'Rivera Culinary Arts & Catering', 'Alex Rivera', '104 Culinary Row, Arts District, Metropolis', 'Fine Dining, Executive Banquets, Molecular Gastronomy', 4.9, 120, 'approved');");

$db->exec("INSERT INTO staff_profiles (user_id, staff_code, department, active_status) VALUES
(3, 'STF-1001', 'Lead Event Coordination', 'active'),
(5, 'STF-1002', 'A/V & Stage Technology', 'active'),
(6, 'STF-1003', 'Guest Hospitality & Protocol', 'active');");
echo "       ✔ Profiles seeded.\n";

// -------------------------------------------------------------
// Step 4: Insert Venues (7 Premium Properties)
// -------------------------------------------------------------
echo "[5/16] Seeding Venues...\n";
$db->exec("INSERT INTO venues (id, name, slug, venue_type, address, district, capacity, base_rate, service_fee_pct, additional_hour_rate, rating, review_count, description, amenities, image_url, badge, status) VALUES
(1, 'Grand Emerald Ballroom', 'grand-emerald-ballroom', 'Ballroom', '742 Evergreen Terrace, Level 4', 'Downtown Financial District', 600, 2400.00, 10.00, 350.00, 4.9, 124, 'The pinnacle of our enterprise collection. Spanning 8,500 square feet with neo-classical crystal chandeliers and integrated Bose acoustics.', 'WiFi, In-house Catering, AV Equipment, Valet Parking, ADA Compliant, Elite Security', '../assets/venue1.png', 'PREMIUM', 'active'),
(2, 'Skyline Vista Lounge', 'skyline-vista-lounge', 'Rooftop', '1200 Waterfront Promenade, Floor 32', 'North Waterfront District', 150, 2400.00, 10.00, 300.00, 4.7, 98, 'Floor-to-ceiling panoramic harbor vistas. Features an open-air terrace, cocktail mixology bar, and private high-speed elevator access.', 'WiFi, Open Air Terrace, High-end Sound, Valet Parking, Breakout Lounge', '../assets/venue2.png', 'POPULAR', 'active'),
(3, 'The Brick & Steel Gallery', 'the-brick-steel-gallery', 'Loft', '45 Industrial Way, Suite B', 'Arts & Design District', 300, 1800.00, 10.00, 250.00, 4.8, 86, 'Exposed industrial brickwork, steel truss ceilings, and museum-grade track lighting. Ideal for creative launches, runway galas, and art showcases.', 'WiFi, Modular Staging, Track Lighting, Acoustic Treatment, Loading Dock', '../assets/venue3.png', 'NEW', 'active'),
(4, 'Crystal Tech Pavilion', 'crystal-tech-pavilion', 'Conference Center', '88 Innovation Parkway', 'Tech Innovation Park', 400, 3200.00, 10.00, 400.00, 5.0, 142, 'Engineered for international symposiums and investor summits. 4K LED multi-screen video walls and simultaneous interpretation booths.', 'Ultra-Fast Fiber, 4K LED Walls, Broadcast Suite, ADA Compliant, Green Rooms', '../assets/venue4.png', 'FLAGSHIP', 'active'),
(5, 'The Legacy Hall', 'the-legacy-hall', 'Historic', '15 University Quadrangle', 'Academic Quarter', 200, 950.00, 10.00, 150.00, 4.6, 64, 'Stately wood-paneled historic hall with cathedral vaulted ceilings. Elegant setting for academic ceremonies, chamber concerts, and formal dinners.', 'WiFi, Podium & PA, Pipe Organ, Valet Parking, Banquet Chairs', '../assets/venue1.png', 'HISTORIC', 'active'),
(6, 'Zen Courtyard Retreat', 'zen-courtyard-retreat', 'Garden', '500 Highland Crest Way', 'West Garden Hills', 80, 1500.00, 10.00, 200.00, 4.9, 52, 'Tranquil Japanese-inspired botanical sanctuary with koi ponds and shaded bamboo pavilions. Perfect for private celebrations and executive retreats.', 'WiFi, Ambient Garden Lighting, Outdoor Heaters, Covered Pavilions', '../assets/venue2.png', 'EXCLUSIVE', 'active'),
(7, 'Waterfront Glasshouse', 'waterfront-glasshouse', 'Atrium', '25 Harbor Pier Boulevard', 'Harbor Marina District', 250, 2800.00, 10.00, 350.00, 4.8, 110, 'Geometric glass architecture offering 360-degree waterfront views with climate-controlled indoor gardens and ambient sunset illumination.', 'WiFi, Full Catering Access, Architectural Lighting, Valet Parking, Sound Rig', '../assets/venue3.png', 'FEATURED', 'active');");
echo "       ✔ Inserted 7 premier venues.\n";

// -------------------------------------------------------------
// Step 5: Insert Catering Packages
// -------------------------------------------------------------
echo "[6/16] Seeding Catering Packages...\n";
$db->exec("INSERT INTO catering_packages (id, caterer_id, title, tier, price_per_event, price_per_guest, min_guests, max_capacity, description, cuisine_type, service_style, features, status) VALUES
(1, 2, 'Gold Luncheon & Seminar Package', 'Gold', 1200.00, 24.00, 30, 100, 'Perfect for daytime corporate seminars, executive symposiums, and seated luncheons. Features light gourmet cuisine prepared with organic farm ingredients.', 'Fine Dining', 'Plated Banquet', '[\"2-Course Gourmet Plated Lunch\",\"Artisanal Coffee & Tea Lounge\",\"Seasonal Canapes on Arrival\",\"Standard Banquet Settings & Linens\"]', 'published'),
(2, 2, 'Platinum Evening Gala Package', 'Platinum', 1850.00, 37.00, 50, 250, 'Full luxury dining experience with open bar mixology, 3-course table service, and sommelier-curated wine selections.', 'Fine Dining', 'Fine Dining Service', '[\"3-Course Fine Dining Plated Dinner\",\"4-Hour Premium Open Bar & Wine Service\",\"Executive Chef Signature Dessert Trio\",\"VIP Green Room Cocktail Service\"]', 'published'),
(3, 2, 'Chef Signature Private Tasting', 'Signature', 2400.00, 48.00, 20, 80, 'An exclusive 5-course culinary journey orchestrated live by Master Chef Alex Rivera with bespoke wine pairing per course.', 'Contemporary Gastronomy', 'Tasting Course', '[\"5-Course Wine-Paired Gastronomy Menu\",\"Executive Chef Live at Captain Table\",\"Midnight Savory Bites & Nightcap Bar\",\"Handcrafted Artisanal Ingredients\"]', 'published');");
echo "       ✔ Inserted 3 catering package tiers.\n";

// -------------------------------------------------------------
// Step 6: Insert Package Menu Items
// -------------------------------------------------------------
echo "[7/16] Seeding Package Menu Items...\n";
$db->exec("INSERT INTO package_menu_items (package_id, course, item_name, dietary_tag, portion_note, unit_value) VALUES
(1, 'Appetizers', 'Organic Heritage Garden Salad', 'Veg', 'Crisp field greens, shaved radishes, champagne vinaigrette', 8.00),
(1, 'Main Course', 'Pan-Seared Citrus Herb Chicken', 'GF', 'Free-range breast, Yukon potato mousseline, asparagus', 18.00),
(1, 'Desserts', 'Artisanal Berry Tartlet', 'Veg', 'Shortcrust pastry, vanilla bean diplomat cream, wild berries', 7.00),
(1, 'Beverages', 'Artisanal Coffee & Botanical Tea Bar', 'Veg', 'Freshly brewed espresso roast, single-origin teas', 4.50),
(2, 'Appetizers', 'Butter-Poached Lobster Tail Canapes', '', 'Tarragon aioli, toasted brioche, Siberian caviar', 15.00),
(2, 'Main Course', 'Filet Mignon au Poivre & Chilean Sea Bass', 'GF', 'Prime center-cut tenderloin, cognac peppercorn reduction, truffle mash', 28.00),
(2, 'Desserts', 'Valrhona Dark Chocolate Molten Sphere', 'Veg', 'Warm salted caramel drizzle, 24k gold leaf, Tahitian vanilla gelato', 11.00),
(2, 'Beverages', 'Top-Shelf Open Bar & Reserve Wine Pairing', '', 'Single malt scotch, botanical gin, vintage Cabernet Sauvignon', 16.00),
(3, 'Appetizers', 'Hamachi Crudo with Yuzu Kosho', 'GF', 'Japanese amberjack, white ponzu, compressed watermelon, micro shiso', 19.00),
(3, 'Main Course', 'A5 Miyazaki Wagyu & Truffle Risotto', 'GF', 'Seared rare Wagyu strip, aged carnaroli rice, black winter truffles', 38.00),
(3, 'Desserts', 'Deconstructed Passion Fruit Meringue Dome', 'Veg', 'Mango coulis, toasted Swiss meringue, coconut sorbet', 14.00),
(3, 'Beverages', 'Curated Grand Cru Wine & Champagne Flights', '', 'Champagne Dom Pérignon arrival, Burgundy and Bordeaux pairings', 22.00);");
echo "       ✔ Inserted 12 package menu items.\n";

// -------------------------------------------------------------
// Step 7: Insert Singular Menu Items (À-La-Carte Add-ons)
// -------------------------------------------------------------
echo "[8/16] Seeding Singular Menu Items...\n";
$db->exec("INSERT INTO singular_menu_items (id, caterer_id, name, item_key, category, emoji, image_url, price, unit_label, quantity_available, min_order_qty, description, dietary_tags, status) VALUES
(1, 2, 'Grilled Tiger Shrimp Skewers', 'grilled-tiger-shrimp', 'Seafood', '🍤', '../assets/dish-shrimp.jpg', 12.50, 'skewer', 200, 5, 'Jumbo black tiger prawns glazed with lemon garlic herb butter.', 'GF', 'active'),
(2, 2, 'Premium Coca-Cola (Glass Bottle 330ml)', 'coca-cola-glass', 'Beverage', '🥤', '../assets/drink-coke.jpg', 3.50, 'bottle', 500, 10, 'Chilled classic glass bottles served on ice with fresh lemon slices.', '', 'active'),
(3, 2, 'Greek Yogurt Parfait Cups', 'greek-yogurt-parfait', 'Dessert', '🍶', '../assets/dish-yogurt.jpg', 7.00, 'cup', 150, 5, 'Creamy organic Greek yogurt with wild flower honey and toasted pistachio granola.', 'Veg', 'active'),
(4, 2, 'French Vanilla Panna Cotta', 'vanilla-panna-cotta', 'Dessert', '🍮', '../assets/dish-dessert.jpg', 9.50, 'cup', 120, 5, 'Silky vanilla bean cream topped with raspberry pomegranate reduction.', 'GF', 'active'),
(5, 2, 'San Pellegrino Sparkling Water (750ml)', 'san-pellegrino', 'Beverage', '💧', '../assets/drink-water.jpg', 4.50, 'bottle', 300, 6, 'Italian natural sparkling mineral water served with fresh lime.', '', 'active'),
(6, 2, 'Truffle & Wild Mushroom Bruschetta', 'truffle-bruschetta', 'Appetizer', '🥖', '../assets/dish-bruschetta.jpg', 6.00, 'piece', 180, 10, 'Crispy rustic sourdough rubbed with garlic and sauteed forest mushrooms.', 'Veg', 'active'),
(7, 2, 'Artisan Farmstead Cheese Board', 'artisan-cheese-board', 'Appetizer', '🧀', '../assets/dish-cheese.jpg', 18.00, 'platter', 80, 2, 'Aged gouda, French brie, Manchego, honeycomb, and seeded crackers.', 'Veg', 'active'),
(8, 2, 'Handcrafted French Macarons (Box of 6)', 'french-macarons', 'Dessert', '🫐', '../assets/dish-macaron.jpg', 14.00, 'box', 100, 3, 'Assorted flavours: Pistachio, salted caramel, dark chocolate, and rose.', 'Veg', 'active');");
echo "       ✔ Inserted 8 singular add-on items.\n";

// -------------------------------------------------------------
// Step 8: Insert Bookings (Pristine Demonstration Records)
// -------------------------------------------------------------
echo "[9/16] Seeding Bookings...\n";
$db->exec("INSERT INTO bookings (id, booking_code, customer_id, venue_id, package_id, event_name, event_date, start_time, end_time, guest_count, duration_hours, venue_cost, package_cost, staffing_cost, addons_cost, subtotal, service_fee, tax_vat, total_amount, payment_status, payment_card_last4, booking_status, caterer_status, special_notes, created_at) VALUES
(1, 'BK-9021', 1, 1, 1, 'Metropolis Annual Corporate Gala', '2026-10-14', '18:00:00', '22:00:00', 320, 4, 2400.00, 1200.00, 640.00, 755.00, 4995.00, 499.50, 399.60, 5894.10, 'paid', '4242', 'confirmed', 'accepted', 'VIP stage layout with podium. Wireless microphones required for keynote speakers.', '2026-09-15 10:30:00'),
(2, 'BK-8843', 1, 2, 2, 'TechCorp Global Executive Dinner', '2026-11-04', '18:30:00', '22:30:00', 150, 4, 2400.00, 1850.00, 640.00, 140.00, 5030.00, 503.00, 402.40, 5935.40, 'paid', '4242', 'confirmed', 'accepted', 'Harbor terrace cocktail reception followed by 3-course seated dinner.', '2026-09-18 14:15:00'),
(3, 'BK-9011', 1, 3, 3, 'Luxe Media Q4 Launch Summit', '2026-12-15', '17:00:00', '23:00:00', 80, 6, 1800.00, 2400.00, 640.00, 276.00, 5116.00, 511.60, 409.28, 6036.88, 'paid', '4242', 'pending', 'pending', 'Creative product reveal on main stage. Pending administrative venue compliance check.', '2026-09-22 09:45:00'),
(4, 'BK-7720', 1, 4, 2, 'Winter Innovation Showcase 2027', '2027-01-20', '09:00:00', '17:00:00', 250, 8, 3200.00, 1850.00, 640.00, 190.00, 5880.00, 588.00, 470.40, 6938.40, 'paid', '4242', 'confirmed', 'accepted', 'All 4K video walls require live feeds from external broadcast unit.', '2026-09-28 16:20:00');");
echo "       ✔ Inserted 4 pristine demo bookings.\n";

// -------------------------------------------------------------
// Step 9: Insert Booking Singular Items
// -------------------------------------------------------------
echo "[10/16] Seeding Booking Singular Items...\n";
$db->exec("INSERT INTO booking_singular_items (id, booking_id, singular_item_id, item_name, emoji, dietary_tag, unit_price, quantity, subtotal, item_status, notes) VALUES
(1, 1, 1, 'Grilled Tiger Shrimp Skewers', '🍤', 'GF', 12.50, 24, 300.00, 'Accepted', 'Passed during executive welcome hour'),
(2, 1, 2, 'Premium Coca-Cola (Glass Bottle 330ml)', '🥤', '', 3.50, 60, 210.00, 'Accepted', 'Pre-stocked in boardroom coolers'),
(3, 1, 3, 'Greek Yogurt Parfait Cups', '🍶', 'Veg', 7.00, 15, 105.00, 'Accepted', 'VIP green room breakfast table'),
(4, 1, 8, 'Handcrafted French Macarons (Box of 6)', '🫐', 'Veg', 14.00, 10, 140.00, 'Accepted', 'Take-home guest favours'),
(5, 2, 6, 'Truffle & Wild Mushroom Bruschetta', '🥖', 'Veg', 6.00, 15, 90.00, 'Accepted', 'Passed during sunset terrace reception'),
(6, 2, 5, 'San Pellegrino Sparkling Water (750ml)', '💧', '', 4.50, 20, 50.00, 'Accepted', 'Table carafes with sliced lemons'),
(7, 3, 7, 'Artisan Farmstead Cheese Board', '🧀', 'Veg', 18.00, 6, 108.00, 'Pending', 'Green room hospitality station'),
(8, 3, 8, 'Handcrafted French Macarons (Box of 6)', '🫐', 'Veg', 14.00, 12, 168.00, 'Pending', 'Midnight dessert enhancement');");
echo "       ✔ Inserted 8 booking singular items.\n";

// -------------------------------------------------------------
// Step 10: Insert Caterer Orders
// -------------------------------------------------------------
echo "[11/16] Seeding Caterer Orders...\n";
$db->exec("INSERT INTO caterer_orders (id, order_code, booking_id, caterer_id, covers_count, preparation_status, package_decision, rejection_reason, created_at) VALUES
(1, 'ORD-2045', 1, 2, 320, 'Preparing', 'Accepted', NULL, '2026-09-15 10:35:00'),
(2, 'ORD-1882', 2, 2, 150, 'Preparing', 'Accepted', NULL, '2026-09-18 14:20:00'),
(3, 'ORD-9011', 3, 2, 80, 'Preparing', 'Pending', NULL, '2026-09-22 09:50:00'),
(4, 'ORD-7720', 4, 2, 250, 'Preparing', 'Accepted', NULL, '2026-09-28 16:25:00');");
echo "       ✔ Inserted 4 caterer kitchen orders.\n";

// -------------------------------------------------------------
// Step 11: Insert Staff Assignments
// -------------------------------------------------------------
echo "[12/16] Seeding Staff Assignments...\n";
$db->exec("INSERT INTO staff_assignments (id, booking_id, staff_id, role_title, shift_time, setup_status, checklist_json, created_at) VALUES
(1, 1, 3, 'Lead Event Coordinator', '16:00 - 23:00', 'in_progress', '{\"c1\":true,\"c2\":true,\"c3\":true,\"c4\":true,\"c5\":false,\"c6\":false,\"c7\":false,\"c8\":false,\"c9\":false,\"c10\":false}', '2026-09-16 09:00:00'),
(2, 2, 5, 'Senior A/V & Stage Engineer', '16:30 - 23:00', 'pending', '{\"c1\":false,\"c2\":false,\"c3\":false,\"c4\":false,\"c5\":false}', '2026-09-19 11:00:00'),
(3, 3, 6, 'Logistics & Guest Services Lead', '15:00 - 23:30', 'pending', '{\"c1\":false,\"c2\":false,\"c3\":false,\"c4\":false,\"c5\":false}', '2026-09-23 10:00:00');");
echo "       ✔ Inserted 3 staff event assignments.\n";

// -------------------------------------------------------------
// Step 12: Insert Chat Messages (Customer <-> Staff)
// -------------------------------------------------------------
echo "[13/16] Seeding Chat Messages...\n";
$db->exec("INSERT INTO chat_messages (sender_id, receiver_id, booking_id, message, is_read, created_at) VALUES
(3, 1, 1, 'Hello Mahmud! I am Sarah Jenkins, your assigned Venue Staff Coordinator for the Grand Emerald Ballroom. Our staff team has verified the stage configuration and 15 banquet tables. How can we assist you with setup?', 1, '2026-10-01 12:28:00'),
(1, 3, 1, 'Hi Sarah! Thanks for reaching out. Could we ensure the wireless lapel microphones and podium sound system are fully tested by 5:00 PM today?', 1, '2026-10-01 12:30:00'),
(3, 1, 1, 'Absolutely Mahmud! Marcus from our technical staff has scheduled the Bose audio rig soundcheck for 4:30 PM. I will personally inspect the lapel mics and backup batteries prior to 5:00 PM.', 1, '2026-10-01 12:32:00'),
(1, 3, 1, 'That is fantastic. We also have 4 VIP guests arriving at 5:30 PM. Can the green room be stocked with the requested beverages?', 0, '2026-10-01 13:05:00'),
(3, 1, 1, 'Yes! The Greek Yogurt Parfaits and chilled San Pellegrino have been delivered by Chef Alex and are staged in the green room.', 0, '2026-10-01 13:12:00');");
echo "       ✔ Inserted 5 direct chat messages.\n";

// -------------------------------------------------------------
// Step 13: Insert Notifications for All Roles
// -------------------------------------------------------------
echo "[14/16] Seeding Notifications...\n";
$db->exec("INSERT INTO notifications (user_id, title, message, type, link_url, is_read, created_at) VALUES
(1, 'Booking #BK-9021 Approved', 'Your reservation for Grand Emerald Ballroom on Oct 14, 2026 has been confirmed by Admin.', 'booking', 'customer-live-progress.php?booking_id=1', 1, '2026-10-01 10:00:00'),
(1, 'Invoice #INV-2026-9021 Issued', 'Payment receipt for $5,894.10 has cleared. Your itemized invoice is ready.', 'payment', 'client-invoice.php?booking_id=1', 0, '2026-10-01 10:05:00'),
(1, 'New Message from Sarah Jenkins', 'Sarah: \"The Greek Yogurt Parfaits and chilled San Pellegrino are staged in the green room.\"', 'message', 'customer-chat.php', 0, '2026-10-01 13:12:00'),
(2, 'New Kitchen Order #ORD-2045', 'Annual Corporate Gala order received for 320 covers at Grand Emerald Ballroom.', 'caterer', 'caterer-order-details.php?order_id=1', 1, '2026-09-15 10:35:00'),
(2, 'Pending Review: Order #ORD-9011', 'Luxe Media Q4 Launch Summit has requested 5-course Chef Tasting (80 covers).', 'caterer', 'caterer-order-details.php?order_id=3', 0, '2026-09-22 09:50:00'),
(3, 'Assigned to Event #BK-9021', 'You have been assigned as Lead Coordinator for Grand Emerald Ballroom on Oct 14, 2026.', 'system', 'staff-event-setup.php?booking_id=1', 1, '2026-09-16 09:00:00'),
(3, 'New Client Message from Mahmud', 'Mahmud: \"We also have 4 VIP guests arriving at 5:30 PM...\"', 'message', 'staff-chat.php', 0, '2026-10-01 13:05:00'),
(4, 'Pending Booking Approval: BK-9011', 'Luxe Media submitted reservation for The Brick & Steel Gallery ($6,036.88).', 'booking', 'admin-booking-approval.php?booking_id=3', 0, '2026-09-22 09:45:00');");
echo "       ✔ Inserted 8 role-targeted notifications.\n";

// -------------------------------------------------------------
// Step 14: Insert Invoices
// -------------------------------------------------------------
echo "[15/16] Seeding Invoices...\n";
$db->exec("INSERT INTO invoices (id, invoice_code, booking_id, customer_id, issue_date, due_date, subtotal, service_fee, vat_tax, total_amount, payment_status, created_at) VALUES
(1, 'INV-2026-9021', 1, 1, '2026-09-15', '2026-09-15', 4995.00, 499.50, 399.60, 5894.10, 'paid', '2026-09-15 10:30:00'),
(2, 'INV-2026-8843', 2, 1, '2026-09-18', '2026-09-18', 5030.00, 503.00, 402.40, 5935.40, 'paid', '2026-09-18 14:15:00'),
(3, 'INV-2026-9011', 3, 1, '2026-09-22', '2026-10-22', 5116.00, 511.60, 409.28, 6036.88, 'paid', '2026-09-22 09:45:00'),
(4, 'INV-2027-7720', 4, 1, '2026-09-28', '2026-09-28', 5880.00, 588.00, 470.40, 6938.40, 'paid', '2026-09-28 16:20:00');");
echo "       ✔ Inserted 4 matching tax invoices.\n";

// -------------------------------------------------------------
// Step 15: Insert Site Legal Policies
// -------------------------------------------------------------
echo "[16/16] Seeding Legal Site Policies...\n";
$db->exec("INSERT INTO site_policies (policy_key, title, content, updated_by) VALUES
('privacy_policy', 'VenuePro Privacy Policy', 'VenuePro Enterprise takes your personal privacy and proprietary event data with the utmost seriousness. This document outlines how client reservations, payment transactions, and catering preferences are handled across our cloud infrastructure.\n\n1. Information Collection: We collect reservation details, contact phone numbers, and guest attendance figures solely for event coordination.\n2. Payment Security: Credit card credentials are processed via SSL encryption and tokenized. Raw card numbers are never stored in plain text.\n3. Third-Party Sharing: Data is strictly shared with assigned caterers and venue staff coordinators for operational fulfillment only.', 4),
('terms_of_service', 'VenuePro Master Terms of Service', 'By accessing VenuePro and booking properties or culinary services, you agree to our standard venue reservation agreement.\n\n1. Reservations & Approvals: All bookings remain pending until confirmed by an authorized administrator.\n2. Cancellation Policy: Free cancellation is available up to 14 business days prior to scheduled execution date.\n3. Damages & Liability: The client agrees to assume responsibility for property damages caused during occupied event hours.', 4),
('contact_support', 'VenuePro Global Support & Concierge', 'Our dedicated event concierge team is available 24 hours a day, 7 days a week to assist enterprise clients and partner kitchens.\n\n• Central Dispatch Desk: +1 (800) 555-VENUE\n• Emergency On-Site Operations: operations@venuepro.com\n• Technical & Platform Support: support@venuepro.com\n• Headquarters: 742 Evergreen Corporate Tower, Floor 14, Metropolis', 4);");
echo "       ✔ Seeded legal policies (Privacy Policy, Terms of Service, Support).\n\n";

echo "============================================================\n";
echo "🎉 DEMO REGENERATION COMPLETED SUCCESSFULLY!\n";
echo "============================================================\n";
echo "Default Accounts (All passwords: password123):\n";
echo "  - Customer: customer@venuepro.com\n";
echo "  - Caterer:  caterer@venuepro.com\n";
echo "  - Staff:    staff@venuepro.com\n";
echo "  - Admin:    admin@venuepro.com\n";
echo "============================================================\n";
