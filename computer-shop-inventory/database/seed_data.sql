-- ==========================================================
-- Sample Seed Data for Computer Shop Inventory System
-- ==========================================================

USE `computer_shop_inventory`;

-- --------------------------------------------------------
-- Users (Default passwords: admin123, staff123, auditor123)
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$wO3nE8kSjZzR.vF/zN4ooeHkQ7x1d4p3D9m2Y1a8B5c7E9g1I3kLm', 'Bryan Cahayag Cole (Admin)', 'admin@computershop.local', 'admin', 'active'),
(2, 'staff', '$2y$10$rC2bE1kSjZzR.vF/zN4ooeHkQ7x1d4p3D9m2Y1a8B5c7E9g1I3kLm', 'Alex Rivera (Staff)', 'staff@computershop.local', 'staff', 'active'),
(3, 'auditor', '$2y$10$tZ9nE4kSjZzR.vF/zN4ooeHkQ7x1d4p3D9m2Y1a8B5c7E9g1I3kLm', 'Noel Montecillo (Auditor)', 'auditor@computershop.local', 'auditor', 'active');

-- --------------------------------------------------------
-- Categories (Computer Hardware Classifications)
-- --------------------------------------------------------
INSERT INTO `categories` (`id`, `name`, `code`, `description`, `icon`) VALUES
(1, 'Processors (CPU)', 'CPU', 'Desktop and workstation central processing units', 'bi-cpu'),
(2, 'Graphics Cards (GPU)', 'GPU', 'Dedicated PCI-Express video and graphics cards', 'bi-gpu-card'),
(3, 'Motherboards (Mobo)', 'MB', 'Motherboards for AMD AM5/AM4 and Intel LGA1700', 'bi-motherboard'),
(4, 'Memory (RAM)', 'RAM', 'DDR4 and DDR5 desktop and laptop memory modules', 'bi-memory'),
(5, 'Storage (SSD & HDD)', 'STR', 'High-speed NVMe M.2 SSDs, SATA SSDs, and 3.5-inch HDDs', 'bi-hdd'),
(6, 'Power Supplies (PSU)', 'PSU', 'Modular and semi-modular ATX power supplies', 'bi-lightning-charge'),
(7, 'PC Cases & Cooling', 'CASE', 'Computer chassis, AIO liquid coolers, and case fans', 'bi-box'),
(8, 'Peripherals', 'PERI', 'Monitors, gaming keyboards, mice, and headsets', 'bi-display'),
(9, 'Cables & Accessories', 'ACC', 'HDMI/DisplayPort cables, CAT6 patch cords, thermal paste', 'bi-plugin'),
(10, 'Pre-built Systems & Laptops', 'SYS', 'Complete desktop towers and gaming laptops', 'bi-laptop');

-- --------------------------------------------------------
-- Suppliers
-- --------------------------------------------------------
INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`) VALUES
(1, 'TechFrontier Distribution Inc.', 'Marco Santos', '+63 917 123 4567', 'sales@techfrontier.ph', 'Pasig City, Metro Manila'),
(2, 'Apex Hardware Supply Corp.', 'Elena Ramos', '+63 920 987 6543', 'orders@apexhardware.com', 'Quezon City, Metro Manila'),
(3, 'Nexus Components Global', 'David Lee', '+63 918 555 1212', 'contact@nexuscomponents.ph', 'Cebu City, Cebu');

-- --------------------------------------------------------
-- Products (Realistic Computer Parts Inventory)
-- --------------------------------------------------------
INSERT INTO `products` (`id`, `sku`, `name`, `category_id`, `brand`, `specifications`, `unit_cost`, `selling_price`, `quantity`, `min_threshold`, `location`) VALUES
-- CPUs
(1, 'CPU-AMD-7800X3D', 'AMD Ryzen 7 7800X3D Processor', 1, 'AMD', '8-Cores, 16-Threads, 4.2GHz Base, 5.0GHz Boost, 104MB Cache, AM5 Socket', 18500.00, 22500.00, 8, 4, 'Cabinet CPU-1'),
(2, 'CPU-INT-14700K', 'Intel Core i7-14700K Processor', 1, 'Intel', '20 Cores (8P+12E), 28 Threads, Up to 5.6GHz, LGA1700, Intel UHD 770', 21000.00, 24800.00, 5, 3, 'Cabinet CPU-1'),
(3, 'CPU-AMD-5600X', 'AMD Ryzen 5 5600X Processor', 1, 'AMD', '6-Cores, 12-Threads, 3.7GHz Base, 4.6GHz Boost, 35MB Cache, AM4 Socket', 7200.00, 8800.00, 2, 5, 'Cabinet CPU-2'), -- LOW STOCK!

-- GPUs
(4, 'GPU-ASUS-4070TIS', 'ASUS TUF Gaming GeForce RTX 4070 Ti SUPER', 2, 'ASUS', '16GB GDDR6X, 256-bit, DLSS 3, 3x DisplayPort 1.4a, 2x HDMI 2.1a', 46000.00, 52500.00, 4, 2, 'Glass Case GPU-A'),
(5, 'GPU-MSI-4060VENT', 'MSI GeForce RTX 4060 Ventus 2X Black OC', 2, 'MSI', '8GB GDDR6, 128-bit, Dual Fan, DLSS 3, Ray Tracing', 16800.00, 19900.00, 9, 3, 'Glass Case GPU-B'),
(6, 'GPU-SAP-7800XT', 'Sapphire Pure AMD Radeon RX 7800 XT', 2, 'Sapphire', '16GB GDDR6, 256-bit, Tri-X Cooling, White Edition', 29000.00, 33500.00, 1, 3, 'Glass Case GPU-B'), -- LOW STOCK!

-- Motherboards
(7, 'MB-ASUS-B650E', 'ASUS ROG STRIX B650E-F GAMING WIFI', 3, 'ASUS', 'AMD AM5, DDR5, PCIe 5.0 x16, 3x M.2 Slots, WiFi 6E, 2.5Gb LAN', 14200.00, 16900.00, 6, 3, 'Shelf MB-1'),
(8, 'MB-MSI-B760M', 'MSI PRO B760M-A WIFI DDR5', 3, 'MSI', 'Intel LGA1700, DDR5 7000+MHz (OC), PCIe 4.0, Dual M.2, 2.5G LAN', 8800.00, 10500.00, 7, 3, 'Shelf MB-2'),

-- RAM
(9, 'RAM-COR-DDR5-32', 'Corsair Vengeance RGB 32GB (2x16GB) DDR5', 4, 'Corsair', '6000MHz, CL30, AMD EXPO & Intel XMP Ready, Black', 6400.00, 7800.00, 12, 4, 'Drawer RAM-1'),
(10, 'RAM-GSK-DDR4-16', 'G.Skill Ripjaws V 16GB (2x8GB) DDR4', 4, 'G.Skill', '3200MHz, CL16-18-18-38, 1.35V, Dual Channel Kit', 2200.00, 2750.00, 3, 5, 'Drawer RAM-2'), -- LOW STOCK!

-- Storage
(11, 'STR-SAM-990PRO2TB', 'Samsung 990 PRO NVMe M.2 SSD 2TB', 5, 'Samsung', 'PCIe Gen 4.0 x4, Up to 7450 MB/s Read, 6900 MB/s Write', 9200.00, 11400.00, 10, 3, 'Drawer STR-1'),
(12, 'STR-KIN-NV2-1TB', 'Kingston NV2 PCIe 4.0 NVMe SSD 1TB', 5, 'Kingston', 'M.2 2280, Up to 3500 MB/s Read, 2100 MB/s Write', 2900.00, 3600.00, 15, 5, 'Drawer STR-1'),
(13, 'STR-SEA-BARR-2TB', 'Seagate BarraCuda 2TB 3.5-inch Internal HDD', 5, 'Seagate', '7200 RPM, SATA 6Gb/s, 256MB Cache', 2800.00, 3400.00, 0, 4, 'Shelf STR-2'), -- OUT OF STOCK!

-- Power Supplies
(14, 'PSU-SEA-FOCUS-850', 'Seasonic FOCUS GX-850 850W Gold', 6, 'Seasonic', '80 PLUS Gold Certified, Fully Modular, ATX 3.0 / PCIe 5.0 Ready', 6900.00, 8400.00, 7, 3, 'Shelf PSU-1'),
(15, 'PSU-COR-CV650', 'Corsair CV Series CV650 650W Power Supply', 6, 'Corsair', '80 PLUS Bronze Certified, Low-Noise 120mm Fan, Fixed Cables', 2700.00, 3350.00, 8, 3, 'Shelf PSU-2'),

-- Cases & Cooling
(16, 'CAS-NZXT-H5FLOW', 'NZXT H5 Flow Compact ATX Mid-Tower Case', 7, 'NZXT', 'Perforated Front Panel, Tempered Glass Side, 2x 120mm Fans Included, Black', 4300.00, 5200.00, 5, 2, 'Rack Case-1'),
(17, 'COOL-DEP-AK620', 'DeepCool AK620 High-Performance CPU Cooler', 7, 'DeepCool', 'Dual-Tower 6 Heatpipes, 2x 120mm Fluid Dynamic Bearing Fans, 260W TDP', 2600.00, 3200.00, 6, 2, 'Shelf Cool-1'),

-- Peripherals
(18, 'PER-ASUS-VG279Q', 'ASUS TUF Gaming VG279QL1A 27" Gaming Monitor', 8, 'ASUS', 'FHD (1920x1080), IPS, 165Hz (OC), 1ms MPRT, FreeSync Premium, HDR400', 11500.00, 13800.00, 4, 2, 'Rack Monitor-A'),
(19, 'PER-LOG-GPROSL', 'Logitech G PRO X SUPERLIGHT Wireless Mouse', 8, 'Logitech', 'HERO 25K Sensor, <63g Ultra Lightweight, 25600 DPI, White', 5900.00, 7200.00, 2, 3, 'Showcase Peri-1'), -- LOW STOCK!
(20, 'PER-RAZ-BLACKV3', 'Razer BlackWidow V3 Tenkeyless Mechanical Keyboard', 8, 'Razer', 'Razer Green Mechanical Switches, Chroma RGB, Doubleshot ABS Keycaps', 4100.00, 4990.00, 6, 2, 'Showcase Peri-2'),

-- Cables & Accessories
(21, 'ACC-UGREEN-DP14', 'UGREEN 8K DisplayPort 1.4 Cable 2M', 9, 'UGREEN', '32.4Gbps, Supports 8K@60Hz, 4K@144Hz, 2K@165Hz, Braided', 450.00, 750.00, 25, 5, 'Pegboard Cable-1'),
(22, 'ACC-CAT6-10M', 'Cat6 High-Speed RJ45 Gigabit Ethernet Cable 10M', 9, 'AMP', 'UTP 24AWG Snagless Molded Boot Patch Cord', 180.00, 320.00, 30, 8, 'Pegboard Cable-2'),
(23, 'ACC-ARCTIC-MX6', 'Arctic MX-6 High-Performance Thermal Paste (4g)', 9, 'Arctic', 'Non-Conductive Carbon Micro-Particle Compound, Spatula Included', 320.00, 550.00, 18, 5, 'Drawer Acc-1'),

-- Systems / Laptops
(24, 'SYS-ASUS-TUF-A15', 'Bryan Custom Gaming Laptop 15.6"', 10, 'ASUS', 'Ryzen 7 7735HS, RTX 4060 8GB, 16GB DDR5, 512GB NVMe, 144Hz FHD', 52000.00, 59990.00, 3, 2, 'Secure Vault 1');

-- --------------------------------------------------------
-- Sample Stock Transactions (Audit Log History)
-- --------------------------------------------------------
INSERT INTO `stock_transactions` (`id`, `transaction_code`, `product_id`, `user_id`, `type`, `purpose`, `reference_no`, `quantity`, `unit_cost`, `unit_price`, `supplier_id`, `recipient`, `notes`, `created_at`) VALUES
(1, 'TXN-20260901-001', 1, 1, 'IN', 'Supplier Delivery', 'PO-TF-8841', 10, 18500.00, NULL, 1, NULL, 'Initial batch shipment of Ryzen 7 7800X3D processors', '2026-09-01 09:15:00'),
(2, 'TXN-20260902-002', 4, 1, 'IN', 'Supplier Delivery', 'PO-TF-8842', 5, 46000.00, NULL, 1, NULL, 'Arrival of ASUS RTX 4070 Ti SUPER stock', '2026-09-02 10:30:00'),
(3, 'TXN-20260903-003', 1, 2, 'OUT', 'Customer Sale', 'INV-2026-1049', 2, NULL, 22500.00, NULL, 'Walk-in Customer (Gaming Rig Build)', 'Sold with thermal paste and cooler', '2026-09-03 14:20:00'),
(4, 'TXN-20260904-004', 4, 2, 'OUT', 'Customer Sale', 'INV-2026-1055', 1, NULL, 52500.00, NULL, 'John Doe (Online Order)', 'Shipped via express courier', '2026-09-04 16:45:00'),
(5, 'TXN-20260905-005', 9, 1, 'IN', 'Supplier Delivery', 'PO-AP-9910', 15, 6400.00, NULL, 2, NULL, 'Restock Corsair DDR5 kits', '2026-09-05 11:00:00'),
(6, 'TXN-20260906-006', 9, 2, 'OUT', 'Customer Sale', 'INV-2026-1062', 3, NULL, 7800.00, NULL, 'CyberCafe Upgrade Project', 'Issued for station 1-3 upgrades', '2026-09-06 13:10:00'),
(7, 'TXN-20260907-007', 3, 2, 'OUT', 'Customer Sale', 'INV-2026-1070', 4, NULL, 8800.00, NULL, 'Student PC Builders', 'Bulk discount applied in sales terminal', '2026-09-07 15:40:00'),
(8, 'TXN-20260908-008', 13, 2, 'OUT', 'Customer Sale', 'INV-2026-1081', 5, NULL, 3400.00, NULL, 'Office CCTV System', 'All remaining 2TB drives issued - stockout reached', '2026-09-08 17:05:00'),
(9, 'TXN-20260909-009', 19, 2, 'OUT', 'Defective/RMA', 'RMA-LOG-004', 1, NULL, 0.00, NULL, 'Logitech Warranty Return', 'Sensor jitter reported by customer within 7-day swap period', '2026-09-09 10:15:00');
