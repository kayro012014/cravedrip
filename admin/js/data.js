/* CraveDrip — Mock Data (replace with PHP/MySQL API calls later) */

const MENU_ITEMS = [
    { id: 1,  name: 'Classic Espresso',    category: 'coffee',  price: 85,  img: '../assets/images/espresso.jpg' },
    { id: 2,  name: 'Vanilla Cappuccino',  category: 'coffee',  price: 120, img: '../assets/images/cappuccino.jpg' },
    { id: 3,  name: 'Caramel Macchiato',   category: 'coffee',  price: 150, img: '../assets/images/caramel-macchiato.jpg' },
    { id: 4,  name: 'Americano',           category: 'coffee',  price: 95,  img: '../assets/images/espresso.jpg' },
    { id: 5,  name: 'Caffe Latte',         category: 'coffee',  price: 130, img: '../assets/images/cappuccino.jpg' },
    { id: 6,  name: 'Iced Mocha Frappe',   category: 'cold',    price: 165, img: '../assets/images/iced-mocha.jpg' },
    { id: 7,  name: 'Cold Brew',           category: 'cold',    price: 145, img: '../assets/images/iced-mocha.jpg' },
    { id: 8,  name: 'Mango Smoothie',      category: 'cold',    price: 130, img: '../assets/images/mango-smoothie.jpg' },
    { id: 9,  name: 'Matcha Latte',        category: 'tea',     price: 140, img: '../assets/images/matcha-latte.jpg' },
    { id: 10, name: 'Earl Grey Tea',        category: 'tea',     price: 95,  img: '../assets/images/matcha-latte.jpg' },
    { id: 11, name: 'Butter Croissant',    category: 'pastry',  price: 75,  img: '../assets/images/croissant.jpg' },
    { id: 12, name: 'Chocolate Cake',      category: 'pastry',  price: 110, img: '../assets/images/chocolate-cake.jpg' },
    { id: 13, name: 'Blueberry Muffin',    category: 'pastry',  price: 85,  img: '../assets/images/croissant.jpg' },
    { id: 14, name: 'Cheese Danish',       category: 'pastry',  price: 90,  img: '../assets/images/croissant.jpg' },
];

const INVENTORY_ITEMS = [
    { id: 1,  name: 'Arabica Espresso Beans', category: 'ingredients', unit: 'kg',  stock: 12.5, reorderLevel: 5,   costPrice: 420,  sellPrice: null },
    { id: 2,  name: 'Robusta Beans',           category: 'ingredients', unit: 'kg',  stock: 8.0,  reorderLevel: 5,   costPrice: 280,  sellPrice: null },
    { id: 3,  name: 'Fresh Milk',              category: 'ingredients', unit: 'L',   stock: 18,   reorderLevel: 10,  costPrice: 75,   sellPrice: null },
    { id: 4,  name: 'Oat Milk',                category: 'ingredients', unit: 'L',   stock: 4,    reorderLevel: 5,   costPrice: 145,  sellPrice: null },
    { id: 5,  name: 'Heavy Cream',             category: 'ingredients', unit: 'L',   stock: 3,    reorderLevel: 4,   costPrice: 210,  sellPrice: null },
    { id: 6,  name: 'Vanilla Syrup',           category: 'ingredients', unit: 'L',   stock: 2.5,  reorderLevel: 2,   costPrice: 320,  sellPrice: null },
    { id: 7,  name: 'Caramel Syrup',           category: 'ingredients', unit: 'L',   stock: 1.5,  reorderLevel: 2,   costPrice: 320,  sellPrice: null },
    { id: 8,  name: 'Chocolate Powder',        category: 'ingredients', unit: 'kg',  stock: 3.2,  reorderLevel: 2,   costPrice: 380,  sellPrice: null },
    { id: 9,  name: 'Matcha Powder',           category: 'ingredients', unit: 'kg',  stock: 0.8,  reorderLevel: 1,   costPrice: 1200, sellPrice: null },
    { id: 10, name: 'Sugar (White)',            category: 'ingredients', unit: 'kg',  stock: 15,   reorderLevel: 5,   costPrice: 65,   sellPrice: null },
    { id: 11, name: 'Mango Puree',             category: 'ingredients', unit: 'L',   stock: 5,    reorderLevel: 3,   costPrice: 180,  sellPrice: null },
    { id: 12, name: 'Ice',                     category: 'ingredients', unit: 'kg',  stock: 0,    reorderLevel: 10,  costPrice: 15,   sellPrice: null },
    { id: 13, name: 'Paper Cups 8oz',          category: 'supplies',    unit: 'pcs', stock: 320,  reorderLevel: 100, costPrice: 3,    sellPrice: null },
    { id: 14, name: 'Paper Cups 12oz',         category: 'supplies',    unit: 'pcs', stock: 450,  reorderLevel: 100, costPrice: 4,    sellPrice: null },
    { id: 15, name: 'Paper Cups 16oz',         category: 'supplies',    unit: 'pcs', stock: 180,  reorderLevel: 100, costPrice: 5,    sellPrice: null },
    { id: 16, name: 'Plastic Straws',          category: 'supplies',    unit: 'pcs', stock: 600,  reorderLevel: 200, costPrice: 1,    sellPrice: null },
    { id: 17, name: 'Coffee Filters',          category: 'supplies',    unit: 'pcs', stock: 200,  reorderLevel: 50,  costPrice: 2,    sellPrice: null },
    { id: 18, name: 'Butter Croissants',       category: 'baked',       unit: 'pcs', stock: 24,   reorderLevel: 10,  costPrice: 35,   sellPrice: 75 },
    { id: 19, name: 'Chocolate Cake Slice',    category: 'baked',       unit: 'pcs', stock: 12,   reorderLevel: 5,   costPrice: 55,   sellPrice: 110 },
    { id: 20, name: 'Blueberry Muffins',       category: 'baked',       unit: 'pcs', stock: 8,    reorderLevel: 8,   costPrice: 40,   sellPrice: 85 },
];

// Sample completed orders for the dashboard (today's history)
const SAMPLE_ORDERS = [
    { id: 'ORD-001', time: '07:18', items: ['Classic Espresso', 'Butter Croissant'],            total: 160,  payment: 'Cash'  },
    { id: 'ORD-002', time: '07:45', items: ['Vanilla Cappuccino x2', 'Mango Smoothie'],         total: 370,  payment: 'GCash' },
    { id: 'ORD-003', time: '08:12', items: ['Caramel Macchiato', 'Chocolate Cake'],             total: 260,  payment: 'Cash'  },
    { id: 'ORD-004', time: '08:54', items: ['Matcha Latte x2'],                                  total: 280,  payment: 'Card'  },
    { id: 'ORD-005', time: '09:30', items: ['Iced Mocha Frappe', 'Cold Brew', 'Blueberry Muffin'], total: 395, payment: 'GCash' },
    { id: 'ORD-006', time: '10:05', items: ['Americano x3'],                                    total: 285,  payment: 'Cash'  },
    { id: 'ORD-007', time: '10:41', items: ['Cheese Danish x2', 'Earl Grey Tea'],               total: 275,  payment: 'Cash'  },
];
