<?php

namespace Tests\Feature;

use Tests\TestCase;

class VillageSearchApiTest extends TestCase
{
    /**
     * Test 1: GET /api/villages returns villages with enriched parent data
     */
    public function test_get_villages_returns_enriched_parent_data()
    {
        $response = $this->getJson('/api/villages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'subdistrict_id',
                        'district_id',
                        'province_id',
                        'subdistrict' => ['id', 'name'],
                        'district'    => ['id', 'name'],
                        'regency'     => ['id', 'name'],
                        'province'    => ['id', 'name'],
                        'parent_display',
                        'full_address',
                    ]
                ]
            ]);
    }

    /**
     * Test 2: Search via query parameter ?search=Talaga
     */
    public function test_search_villages_by_keyword()
    {
        $response = $this->getJson('/api/villages?search=Talaga');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNotEmpty($data);
        $talagasari = collect($data)->firstWhere('name', 'Talagasari');
        $this->assertNotNull($talagasari);
        $this->assertEquals('Kawalu', $talagasari['subdistrict']['name']);
        $this->assertEquals('Kota Tasikmalaya', $talagasari['district']['name']);
        $this->assertEquals('Jawa Barat', $talagasari['province']['name']);
        $this->assertEquals('Kawalu • Kota Tasikmalaya', $talagasari['parent_display']);
    }

    /**
     * Test 3: Search via dedicated endpoint GET /api/villages/search?q=Talaga
     */
    public function test_search_endpoint_with_q_param()
    {
        $response = $this->getJson('/api/villages/search?q=Talagasari');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNotEmpty($data);
        $first = $data[0];
        $this->assertEquals('Talagasari', $first['name']);
        $this->assertEquals(41, $first['subdistrict_id']);
        $this->assertEquals(27, $first['district_id']);
        $this->assertEquals(1, $first['province_id']);
    }

    /**
     * Test 4: Backward compatibility with ?subdistrict_id=41
     */
    public function test_villages_by_subdistrict_id()
    {
        $response = $this->getJson('/api/villages?subdistrict_id=41');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertEquals(41, $item['subdistrict_id']);
        }
    }
}
