<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\Medication;
use Illuminate\Support\Facades\DB;

class MedicineService
{
    public function createMedicine(array $data)
    {
        return DB::transaction(function () use ($data) {
            $medicine = Medication::create([
                'scientific_name' => $data['scientific_name'],
                'commercial_name' => $data['commercial_name'],
                'category_id' => $data['category_id'],
                'manufacturer' => $data['manufacturer'],
                'quantity' => $data['quantity'],
                'expiry_date' => $data['expiry_date'],
                'price' => $data['price'],
            ]);


            return $medicine;
        });
    }


    public function BrowseMedicineByCategory(int $categoryId){

         return Medication::where('category_id',$categoryId)->with('category')->orderBy('commercial_name')
        ->get();

    }
    public function BrowseMedicineByName(string $name) {

        return Medication::where('commercial_name','like','%'.$name.'%')->with('category')->orderBy('commercial_name')
        ->get();



    }
    public function show(int $id){

        return Medication::findOrFail($id);
    }

    public function addToFavoritesWithCheck($userId, $medicineId): array
    {
        if (!Medication::where('id', $medicineId)->exists()) {
            throw new \Exception('الدواء المحدد غير موجود');
        }

        return DB::transaction(function () use ($userId, $medicineId) {
            $favorite = Favorite::firstOrCreate(
                [
                    'user_id' => $userId,
                    'medicine_id' => $medicineId
                ],
                [
                    'user_id' => $userId,
                    'medicine_id' => $medicineId
                ]
            );

            $wasRecentlyCreated = $favorite->wasRecentlyCreated;

            return [
                'favorite' => $favorite,
                'is_new' => $wasRecentlyCreated,
                'message' => $wasRecentlyCreated
                    ? 'تمت إضافة الدواء إلى المفضلة بنجاح'
                    : 'الدواء موجود مسبقاً في المفضلة'
            ];
        });
    }
        public function removeFromFavoritesWithCheck($userId, $medicineId): array
    {
        return DB::transaction(function () use ($userId, $medicineId) {
            $deleted = Favorite::where('user_id', $userId)
                ->where('medicine_id', $medicineId)
                ->delete();

            if ($deleted === 0) {
                throw new \Exception('الدواء لم يكن موجوداً في المفضلة');
            }

            return [
                'deleted' => true,
                'message' => 'تمت إزالة الدواء من المفضلة'
            ];
        });
    }



    public function getUserFavoritesWithDetails($userId)
    {
        return Favorite::with([
            'Medication' => function($query) {
                $query->select('id', 'scientific_name', 'commercial_name', 'price');
            },
            'Medication.category' => function($query) {
                $query->select('id', 'name');
            }
        ])
        ->where('user_id', $userId)
        ->get()
        ->map(function($item) {
            return [
                'id' => $item->id,
                'medicine' => $item->Medication,
                'added_at' => $item->created_at->format('Y-m-d H:i')
            ];
        });
    }








}
