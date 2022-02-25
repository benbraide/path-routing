<?php

namespace Benbraide\PathRouting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class PathRoutingController extends Controller
{
    public function handle(Request $request){
        return static::GetView($request);
    }

    public static function GetView($request = null, $isAjax = false, $path = null){
        $path = ($path ?: static::ExtractPath($request));
        $ajaxPrefix = config('path_routing.ajax_prefix', 'ajax');

        if ($isAjax){
            $path = "{$ajaxPrefix}.{$path}";
        }

        $info = static::FindPath($request, $path, $ajaxPrefix);
        if (!$info){
            if (Str::startsWith($path, "{$ajaxPrefix}.")){
                $notFoundSuffix = config('path_routing.not_found_suffix', '404');
                return view("{$ajaxPrefix}.{$notFoundSuffix}");
            }

            $welcomeView = config('path_routing.welcome_view', 'welcome');
            return view($welcomeView, compact('path'));
        }

        if (is_string($info)){
            return view($info);
        }

        return view($info['path'], [
            'args' => $info['args'],
        ]);
    }

    public static function ExtractPath($request){
        $path = preg_replace('/\/{2,}/', '/', ($request ? $request->path() : FacadesRequest::path()));
        return str_replace('/', '.', trim($path, '/'));
    }

    public static function MergePath($value, $path, $startsWith, $prefix){
        $prefix = ($prefix ? "{$prefix}." : '');
        $value = ($value ? ".{$value}" : '');

        if ($startsWith && Str::startsWith($path, "{$startsWith}.")){
            $startsWithLength = (strlen($startsWith) + 1);
            $path = substr($path, $startsWithLength);
            return ("{$startsWith}.{$prefix}{$path}{$value}");
        }

        return "{$prefix}{$path}{$value}";
    }

    public static function FindPath($request, $path = null, $startsWith = 'ajax', $prefix = null){
        $path = ($path ?: static::ExtractPath($request));
        $fullPath = static::MergePath(null, $path, $startsWith, $prefix);

        if (View::exists($fullPath)){
            return $fullPath;
        }

        if ($startsWith){
            if ($path === $startsWith){
                $homePath = static::MergePath('home', $path, null, $prefix);
                if (View::exists($homePath)){
                    return $homePath;
                }

                $homePath = static::MergePath('index', $path, null, $prefix);
                if (View::exists($homePath)){
                    return $homePath;
                }
            }

            if (!Str::startsWith($path, "{$startsWith}.")){
                return null;
            }
        }

        $homePath = static::MergePath('home', $path, $startsWith, $prefix);
        if (View::exists($homePath)){
            return $homePath;
        }

        $homePath = static::MergePath('index', $path, $startsWith, $prefix);
        if (View::exists($homePath)){
            return $homePath;
        }

        if (!$prefix){
            $pathLength = strlen($path);
            for ($offset = 0; $offset < $pathLength; ){
                $offset = strpos($path, '.', $offset);
                if ($offset !== false){
                    $targetPath = static::MergePath(null, substr($path, 0, $offset), $startsWith, 'dynamic');
                    ++$offset;

                    if (substr($path, $offset, 1) !== '?' && View::exists($targetPath)){
                        return array(
                            'path' => $targetPath,
                            'args' => explode('.', substr($path, $offset)),
                        );
                    }
                }
                else{
                    $offset = $pathLength;
                    $targetPath = static::MergePath(null, $path, $startsWith, 'dynamic');
                    if (View::exists($targetPath)){
                        return array(
                            'path' => $targetPath,
                            'args' => [],
                        );
                    }
                }
            }
        }

        return null;
    }
}
