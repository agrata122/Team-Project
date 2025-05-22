<?php
/**
 * Category Popup Component
 * 
 * A reusable sidebar component that displays category options
 * with icons and count indicators. Each category is clickable
 * and redirects to its respective page.
 */

// Sample category data with more appropriate icons and page links
$categories = [
    [
        'name' => 'Butcher',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5cb85c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.5 5.5c.83.83 2 2 2 3.5a4.5 4.5 0 0 1-9 0c0-1.5 1.17-2.67 2-3.5"></path><path d="M8.5 2.5A3.5 3.5 0 0 0 12 6a3.5 3.5 0 0 0 3.5-3.5"></path><path d="M14 12.75V21a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-8.25"></path><path d="M3 9h18"></path></svg>',
        'count' => 5,
       'link' => 'category_page.php?category=butcher'
    ],
    [
        'name' => 'Green Grocer',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5cb85c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 22s3-5 7-7c.5 0 2 3 2 3s2-5 7-3c.5 0 3 3 3 3"></path><circle cx="12" cy="7" r="5"></circle></svg>',
        'count' => 6,
       'link' => 'category_page.php?category=greengrocer'
    ],
    [
        'name' => 'Fish Monger',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5cb85c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6"></path><path d="M6.5 12c-.94 3.46-4.94 6-8.5 6"></path><path d="M6.5 12H10"></path><path d="M18 12c.94 3.46 4.94 6 8.5 6"></path><path d="M18 12h-3.5"></path><path d="M12 10v4"></path><path d="M22 8c0-3.5-2.5-6-6-6"></path><path d="M8 20c0 1.5.5 2 2 2"></path></svg>',
        'count' => 7,
        'link' => 'category_page.php?category=fishmonger'
    ],
    [
        'name' => 'Bakery',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5cb85c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10a6 6 0 0 0-12 0v3.6c0 .8.7 1.4 1.5 1.4h9c.8 0 1.5-.6 1.5-1.4V10z"></path><path d="m4 19 5-1"></path><path d="m20 19-5-1"></path><path d="M5 12v7"></path><path d="M19 12v7"></path><path d="M12 16v1"></path><path d="M8 5.03C8 3.9 8.9 3 10.03 3h3.94C15.1 3 16 3.9 16 5.03V8H8V5.03z"></path></svg>',
        'count' => 12,
        'link' => 'category_page.php?category=bakery'
    ],
    [
        'name' => 'Delicatessen',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5cb85c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v6c0 1.1-.9 2-2 2H4a2 2 0 0 1-2-2V3"></path><path d="M10 21h4"></path><path d="M15 21h5"></path><path d="M4 21h4"></path><path d="M10 3v18"></path><path d="M20 3v18"></path><line x1="4" y1="7" x2="20" y2="7"></line></svg>',
        'count' => 16,
        'link' => 'category_page.php?category=delicatessen'
    ]
];

// Function to render the category item
function renderCategoryItem($name, $icon, $count, $link) {
    return '
    <a href="' . $link . '" class="category-item">
        <div class="category-icon-name">
            <div class="category-icon">
                ' . $icon . '
            </div>
            <div class="category-name">' . $name . '</div>
        </div>
        <div class="category-count">' . $count . '</div>
    </a>
    ';
}
?>

<div class="category-popup">
    <h2 class="category-title">Category</h2>
    <div class="category-divider"></div>
    
    <div class="category-list">
        <?php foreach ($categories as $category): ?>
            <?php echo renderCategoryItem($category['name'], $category['icon'], $category['count'], $category['link']); ?>
        <?php endforeach; ?>
    </div>
</div>

<link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/category_popup.css">