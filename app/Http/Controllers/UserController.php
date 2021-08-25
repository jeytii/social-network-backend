<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get the necessary columns for basic data.
     *
     * @var array
     */
    private $basic_columns = ['slug', 'name', 'username', 'gender', 'image_url'];

    /**
     * Get 20 paginated user models.
     *
     * @return \Illuminate\Http\Response
     */
    public function get()
    {
        // Format each user model with only the necessary columns.
        $data = User::select($this->basic_columns)->paginate(20);
        
        // If there are still remaining items.
        $hasMore = $data->hasMorePages();

        // Increment the current offset/page by 1 if there are still more items left.
        $nextOffset = $data->hasMorePages() ? $data->currentPage() + 1 : null;

        return response()->json([
            'data' => $data->items(),
            'has_more' => $hasMore,
            'next_offset' => $nextOffset,
        ]);
    }

    /**
     * Get 3 suggested user models.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSuggested()
    {
        // Get 3 random users with basic data.
        $data = User::inRandomOrder()->limit(3)->get($this->basic_columns);

        return response()->json(compact('data'));
    }
}
