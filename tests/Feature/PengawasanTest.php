<?php

namespace Tests\Feature;

use App\Models\Pengawasan;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PengawasanTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_loads(): void
    {
        $response = $this->get('/pengawasan');
        $response->assertStatus(200);
    }

    public function test_can_create_pelaku_usaha(): void
    {
        $data = [
            'nama_pelaku_usaha' => 'CV Test',
            'nib' => '0299000999999',
            'jenis_penanaman_modal' => 'PMDN',
            'skala_usaha' => 'Usaha Mikro',
            'tingkat_risiko' => 'Rendah',
        ];

        $response = $this->post('/pengawasan', $data);
        $response->assertRedirect();

        $this->assertDatabaseHas('pengawasan', [
            'nama_pelaku_usaha' => 'CV Test',
            'nib' => '0299000999999',
        ]);
    }

    public function test_can_store_nib_with_leading_zero(): void
    {
        $data = [
            'nama_pelaku_usaha' => 'CV Leading Zero',
            'nib' => '0299000000001',
        ];

        $this->post('/pengawasan', $data);

        $this->assertDatabaseHas('pengawasan', [
            'nib' => '0299000000001',
        ]);
    }

    public function test_can_store_tingkat_risiko(): void
    {
        $data = [
            'nama_pelaku_usaha' => 'CV Risiko',
            'tingkat_risiko' => 'Tinggi',
        ];

        $this->post('/pengawasan', $data);

        $this->assertDatabaseHas('pengawasan', [
            'tingkat_risiko' => 'Tinggi',
        ]);
    }

    public function test_can_delete_pelaku_usaha(): void
    {
        $pengawasan = Pengawasan::create([
            'nama_pelaku_usaha' => 'CV Hapus',
        ]);

        $this->delete("/pengawasan/{$pengawasan->id}");
        $this->assertDatabaseMissing('pengawasan', ['id' => $pengawasan->id]);
    }

    public function test_index_combined_search_and_filters(): void
    {
        Pengawasan::create([
            'nama_pelaku_usaha' => 'CV Alpha',
            'nib' => '0299000000001',
            'tingkat_risiko' => 'Tinggi',
            'nomor_sanksi' => 'S-001/2026',
        ]);
        Pengawasan::create([
            'nama_pelaku_usaha' => 'CV Beta',
            'nib' => '0299000000002',
            'tingkat_risiko' => 'Rendah',
            'nomor_sanksi' => 'S-002/2026',
        ]);

        $response = $this->get('/pengawasan?search=Alpha&tingkat_risiko=Tinggi');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Pengawasan/Index')
                ->where('filters.search', 'Alpha')
                ->where('filters.tingkat_risiko', 'Tinggi')
                ->where('filters.per_page', 15)
                ->has('pengawasan.data', 1)
                ->where('pengawasan.data.0.nama_pelaku_usaha', 'CV Alpha')
        );
    }

    public function test_index_per_page_and_page_two_preserve_query(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Pengawasan::create([
                'nama_pelaku_usaha' => "CV Per Page {$i}",
                'tingkat_risiko' => 'Rendah',
            ]);
        }

        $response = $this->get('/pengawasan?search='.urlencode('Per Page').'&tingkat_risiko=Rendah&per_page=10&page=2');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Pengawasan/Index')
                ->where('filters.search', 'Per Page')
                ->where('filters.tingkat_risiko', 'Rendah')
                ->where('filters.per_page', 10)
                ->has('pengawasan.data', 2)
                ->where('pengawasan.current_page', 2)
                ->where('pengawasan.last_page', 2)
                ->where('pengawasan.per_page', 10)
                ->where('pengawasan.total', 12)
                ->where('pengawasan.from', 11)
                ->where('pengawasan.to', 12)
        );
    }

    public function test_index_rejects_invalid_per_page(): void
    {
        Pengawasan::create(['nama_pelaku_usaha' => 'CV Invalid']);

        $response = $this->get('/pengawasan?per_page=999');

        $response->assertSessionHasErrors('per_page');
        $response->assertRedirect();
    }

    public function test_show_works_without_nomor_sanksi(): void
    {
        $pengawasan = Pengawasan::create([
            'nama_pelaku_usaha' => 'CV Tanpa Nomor',
        ]);

        $response = $this->get("/pengawasan/{$pengawasan->id}");

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Pengawasan/Show')
                ->where('pengawasan.nama_pelaku_usaha', 'CV Tanpa Nomor')
                ->where('pengawasan.nomor_sanksi', null)
        );
    }

    public function test_index_runs_two_pengawasan_select_queries_small_page(): void
    {
        Pengawasan::create(['nama_pelaku_usaha' => 'CV Query Small']);

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries) {
            if (str_contains($query->sql, 'from "pengawasan"')) {
                $queries[] = $query->sql;
            }
        });

        $this->get('/pengawasan?per_page=10');

        $selects = array_filter($queries, fn ($sql) => str_starts_with($sql, 'select'));
        $this->assertCount(2, $selects);
    }

    public function test_index_runs_two_pengawasan_select_queries_large_page(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            Pengawasan::create(['nama_pelaku_usaha' => "CV Query Large {$i}"]);
        }

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries) {
            if (str_contains($query->sql, 'from "pengawasan"')) {
                $queries[] = $query->sql;
            }
        });

        $this->get('/pengawasan?per_page=50');

        $selects = array_filter($queries, fn ($sql) => str_starts_with($sql, 'select'));
        $this->assertCount(2, $selects);
    }
}
