<?php

namespace App\Http\Controllers;

use App\Events\AdminNotificationEvent;
use App\Http\Requests\AddMdicineRequest;
use App\Http\Requests\BrowseMedicineByCategoryRequest;
use App\Http\Requests\BrowseMedicineByName;
use App\Http\Requests\FavoriteRequest;
use App\Models\Favorite;
use App\Services\MedicineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicineController extends Controller
{
    protected $medicineService;

    public function __construct(MedicineService $medicineService)
    {
        $this->medicineService = $medicineService;

    }

    public function store(AddMdicineRequest $request): JsonResponse
    {
        try {
            $medicine = $this->medicineService->createMedicine($request->validated());
            $medicine['expiry_date'] = Carbon::createFromFormat('Y-m-d', $medicine['expiry_date']);
            event(new AdminNotificationEvent('تم إضافة دواء جديد', $medicine));

            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة الدواء بنجاح',
                'data' => $medicine
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إضافة الدواء',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function byCategory(BrowseMedicineByCategoryRequest $request)
    {
        $medicines = $this->medicineService->BrowseMedicineByCategory($request->category_id);

        return response()->json([
            'success' => true,
            'count' => $medicines->count(),
            'data' => $medicines
        ]);
    }
    public function byName(BrowseMedicineByName $request)
    {
        $medicines = $this->medicineService->BrowseMedicineByName($request->commercial_name);

        return response()->json([
            'success' => true,
            'count' => $medicines->count(),
            'data' => $medicines
        ]);


}
    public function show($id){
        $medicines = $this->medicineService->show($id);
        if(!$medicines){
            return response()->json([
                'success' => false,
                'data' => ''
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $medicines
        ]);
    }
    public function add(FavoriteRequest $request): JsonResponse
    {
        try {
            $result = $this->medicineService->addToFavoritesWithCheck(
                Auth::id(),
                $request->medicine_id
            );

            return response()->json([
                'success' => true,
                'is_new' => $result['is_new'],
                'message' => $result['message'],
                'data' => $result['favorite']
            ], $result['is_new'] ? 201 : 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function remove(FavoriteRequest $request): JsonResponse
    {
        try {
            $deleted = $this->medicineService->removeFromFavoritesWithCheck(
                Auth::id(),
                $request->medicine_id
            );

            return response()->json([
                'success' => true,
                'message' => 'تمت إزالة الدواء من المفضلة',
                'deleted' => $deleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إزالة الدواء من المفضلة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $favorites = $this->medicineService->getUserFavoritesWithDetails(Auth::id());

            return response()->json([
                'success' => true,
                'count' => $favorites->count(),
                'data' => $favorites
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب قائمة المفضلة',
                'error' => $e->getMessage()
            ], 500);
        }
    }





}

