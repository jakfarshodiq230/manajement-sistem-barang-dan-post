<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q', '');
        $itemsPerPage = $request->query('itemsPerPage', 10);
        $all = $request->query('all', false);

        $query = Module::with('parent');

        if ($q) {
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('slug', 'like', "%{$q}%");
        }

        if ($all) {
            return response()->json($query->orderBy('sequence')->get());
        }

        $paginator = $query->orderBy('sequence')->paginate($itemsPerPage);

        return response()->json([
            'modules' => $paginator->items(),
            'totalModules' => $paginator->total(),
        ]);
    }

    public function navigation(Request $request)
    {
        $modules = Module::orderBy('sequence')->get();

        $buildTree = function ($elements, $parentId = null) use (&$buildTree) {
            $branch = [];
            foreach ($elements as $element) {
                if ($element->parent_id == $parentId) {
                    $children = $buildTree($elements, $element->id);
                    $node = [
                        'title' => $element->name,
                        'icon' => ['icon' => $element->icon ?: 'ri-circle-line'],
                        'action' => 'read',
                        'subject' => $element->name,
                    ];
                    $element->children = $children;
                    $branch[] = $element;
                }
            }
            return $branch;
        };

        $treeElements = $buildTree($modules);
        
        // Group by category for top level items
        $grouped = collect($treeElements)->groupBy('category');
        $navigation = [];
        
        foreach ($grouped as $category => $items) {
            if (!empty($category)) {
                $navigation[] = ['heading' => strtoupper($category)];
            }
            
            foreach ($items as $item) {
                $children = $buildTree($modules, $item->id);
                $node = [
                    'title' => $item->name,
                    'icon' => ['icon' => $item->icon ?: 'ri-circle-line'],
                    'action' => 'read',
                    'subject' => $item->name,
                ];
                if ($children) {
                    // Convert children to proper structure
                    $nodeChildren = [];
                    foreach ($children as $child) {
                        $nodeChildren[] = [
                            'title' => $child->name,
                            'icon' => ['icon' => $child->icon ?: 'ri-circle-line'],
                            'action' => 'read',
                            'subject' => $child->name,
                            'to' => ['path' => '/' . $child->slug]
                        ];
                    }
                    $node['children'] = $nodeChildren;
                } else {
                    $node['to'] = ['path' => '/' . $item->slug];
                }
                $navigation[] = $node;
            }
        }

        return response()->json($navigation);
    }

    public function store(Request $request)
    {
        if (!request()->user()->can('Modules Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:modules,slug',
            'icon' => 'nullable|string|max:255',
            'sequence' => 'nullable|integer',
            'parent_id' => 'nullable|exists:modules,id',
            'category' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $module = Module::create([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'icon' => $request->icon,
            'sequence' => $request->sequence ?? 0,
            'parent_id' => $request->parent_id,
            'category' => $request->category,
            'status' => $request->status ?? 'Aktif',
            'description' => $request->description,
        ]);

        return response()->json(['message' => 'Module created successfully', 'module' => $module], 201);
    }

    public function show(Module $module)
    {
        return response()->json($module);
    }

    public function update(Request $request, Module $module)
    {
        if (!request()->user()->can('Modules Write')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:modules,slug,' . $module->id,
            'icon' => 'nullable|string|max:255',
            'sequence' => 'nullable|integer',
            'parent_id' => 'nullable|exists:modules,id',
            'category' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $module->update([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'icon' => $request->icon,
            'sequence' => $request->sequence ?? $module->sequence,
            'parent_id' => $request->parent_id,
            'category' => $request->has('category') ? $request->category : $module->category,
            'status' => $request->status ?? $module->status,
            'description' => $request->description ?? $module->description,
        ]);

        return response()->json(['message' => 'Module updated successfully', 'module' => $module]);
    }

    public function destroy(Module $module)
    {
        if (!request()->user()->can('Modules Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $module->delete();
        return response()->json(null, 204);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'modules' => 'required|array',
            'modules.*.id' => 'required|exists:modules,id',
            'modules.*.sequence' => 'required|integer',
            'modules.*.category' => 'nullable|string|max:255',
            'modules.*.parent_id' => 'nullable|exists:modules,id',
        ]);

        foreach ($request->modules as $item) {
            Module::where('id', $item['id'])->update([
                'sequence' => $item['sequence'],
                'category' => $item['category'] ?? null,
                'parent_id' => $item['parent_id'] ?? null,
            ]);
        }

        return response()->json(['message' => 'Modules reordered successfully']);
    }
}
