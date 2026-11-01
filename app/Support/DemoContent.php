<?php

namespace App\Support;

class DemoContent
{
    public static function posts(): array
    {
        return [
            ['slug' => 'cleaner-laravel-with-actions', 'title' => 'Cleaner Laravel code starts with smaller actions.', 'excerpt' => 'A practical look at moving business logic out of controllers, one focused action at a time. Here’s what I learned while refactoring my latest project.', 'category' => 'Laravel', 'author' => 'Muhtasim Riyon', 'initials' => 'MR', 'color' => 'green', 'time' => '8 min read', 'date' => 'Nov 1', 'likes' => 124, 'comments' => 18, 'tags' => ['laravel', 'architecture'], 'cover' => 'laravel', 'featured' => true],
            ['slug' => 'understanding-javascript-closures', 'title' => 'JavaScript closures finally clicked. Here’s how.', 'excerpt' => 'Forget the textbook definitions. Let’s build a tiny counter and understand what a closure actually remembers.', 'category' => 'JavaScript', 'author' => 'Sarah Ahmed', 'initials' => 'SA', 'color' => 'purple', 'time' => '5 min read', 'date' => 'Oct 31', 'likes' => 86, 'comments' => 12, 'tags' => ['javascript', 'beginners'], 'cover' => 'javascript', 'featured' => false],
            ['slug' => 'database-indexes-explained', 'title' => 'The database index that made my query 40× faster', 'excerpt' => 'From a slow dashboard to a snappy experience: reading query plans, choosing indexes, and measuring the difference.', 'category' => 'Database', 'author' => 'Tanvir Hasan', 'initials' => 'TH', 'color' => 'orange', 'time' => '7 min read', 'date' => 'Oct 30', 'likes' => 203, 'comments' => 26, 'tags' => ['mysql', 'performance'], 'cover' => 'database', 'featured' => false],
            ['slug' => 'learning-in-public', 'title' => 'Day 12: building the habit of learning in public', 'excerpt' => 'You don’t need to be an expert to share what you know. A few small lessons from keeping a daily developer journal.', 'category' => 'Daily Learning', 'author' => 'Muhtasim Riyon', 'initials' => 'MR', 'color' => 'green', 'time' => '4 min read', 'date' => 'Oct 29', 'likes' => 57, 'comments' => 9, 'tags' => ['learning', 'career'], 'cover' => 'learning', 'featured' => false],
            ['slug' => 'designing-for-developers', 'title' => 'Good interfaces give your code room to breathe', 'excerpt' => 'A developer’s field notes on spacing, typography, and the small decisions that make products feel effortless.', 'category' => 'UI / UX', 'author' => 'Nadia Islam', 'initials' => 'NI', 'color' => 'pink', 'time' => '6 min read', 'date' => 'Oct 28', 'likes' => 92, 'comments' => 14, 'tags' => ['design', 'css'], 'cover' => 'design', 'featured' => false],
        ];
    }
}
