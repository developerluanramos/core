<?php

namespace Biigle\Http\Controllers\Views;

use Modules;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;

class SearchController extends Controller
{
    /**
     * Shows the search page
     *
     * @param Guard $auth
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Guard $auth, Request $request)
    {
        $query = $request->input('q', '');
        // Type (e.g. projects, volumes)
        $type = $request->input('t', '');
        $user = $auth->user();

        $args = compact('user', 'query', 'type');
        $values = Modules::callControllerMixins('search', $args);

        if (array_key_exists('results', $values)) {
            if ($query) {
                $values['results']->appends('q', $query);
            }

            if ($type) {
                $values['results']->appends('t', $type);
            }
        }

        return view('search.index', array_merge($args, $values));
    }
}
