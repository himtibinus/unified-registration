<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class AddEventData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add Expected List of Target Universities, as shown on the Sponsorship Proposal
        // 1-10
        DB::table('universities')->insert(['name' => 'UI - Universitas Indonesia']);
        DB::table('universities')->insert(['name' => 'UNJ - Universitas Negeri Jakarta']);
        DB::table('universities')->insert(['name' => 'UNTAR - Universitas Tarumanegara']);
        DB::table('universities')->insert(['name' => 'USAKTI - Universitas Trisakti']);
        DB::table('universities')->insert(['name' => 'UPH - Universitas Pelita Harapan']);
        DB::table('universities')->insert(['name' => 'UMN - Universitas Multimedia Nusantara']);
        DB::table('universities')->insert(['name' => 'UEU - Universitas Esa Unggul']);
        DB::table('universities')->insert(['name' => 'UNIKA Atma Jaya']);
        DB::table('universities')->insert(['name' => 'UBM - Universitas Bunda Mulia']);
        DB::table('universities')->insert(['name' => 'ITB - Institut Teknologi Bandung']);
        // 11-20
        DB::table('universities')->insert(['name' => 'UNPAD - Universitas Padjajaran']);
        DB::table('universities')->insert(['name' => 'UNIKOM - Universitas Komputer Indonesia']);
        DB::table('universities')->insert(['name' => 'UNPAR - Universitas Katolik Parahyangan']);
        DB::table('universities')->insert(['name' => 'UG - Universitas Gunadarma']);
        DB::table('universities')->insert(['name' => 'President University - Universitas Presiden']);
        DB::table('universities')->insert(['name' => 'BSI - Universitas Bina Sarana Informatika']);
        DB::table('universities')->insert(['name' => 'UMB - Universitas Mercu Buana']);
        DB::table('universities')->insert(['name' => 'Universitas Pancasila']);
        DB::table('universities')->insert(['name' => 'Universitas Pertamina']);
        DB::table('universities')->insert(['name' => 'UNDIP - Universitas Diponegoro']);
        // 21-30
        DB::table('universities')->insert(['name' => 'IPB - Institut Pertanian Bogor']);
        DB::table('universities')->insert(['name' => 'UNHAS - Universitas Hasanuddin']);
        DB::table('universities')->insert(['name' => 'ITERA - Institut Teknologi Sumatera']);
        DB::table('universities')->insert(['name' => 'UNBRAW - Institut Brawijaya']);
        DB::table('universities')->insert(['name' => 'ITS - Institut Teknologi Sepuluh November']);
            // #26 - UPN: Divide into 3 regions
            DB::table('universities')->insert(['name' => 'UPN "Veteran" - DKI Jakarta']);
            DB::table('universities')->insert(['name' => 'UPN "Veteran" - DI Yogyakarta']);
            DB::table('universities')->insert(['name' => 'UPN "Veteran" - Jawa Timur']);
        DB::table('universities')->insert(['name' => 'YARSI - Universitas Yarsi']);
        DB::table('universities')->insert(['name' => 'UIN Syarif Hidayatullah']);
        DB::table('universities')->insert(['name' => 'UNS - Universitas Sebelas Maret']);
        DB::table('universities')->insert(['name' => 'UGM - Universitas Gadjah Mada']);
        // 31-40
        DB::table('universities')->insert(['name' => 'UII - Universitas Islam Indonesia']);
        DB::table('universities')->insert(['name' => 'UBAYA - Universitas Surabaya']);
        DB::table('universities')->insert(['name' => 'Telkom University - Universitas Telkom']);
        DB::table('universities')->insert(['name' => 'UNNES - Universitas Negeri Semarang']);
        DB::table('universities')->insert(['name' => 'UNP - Universitas Negeri Padang']);
        DB::table('universities')->insert(['name' => 'UNUD - Universitas Udayana']);
        DB::table('universities')->insert(['name' => 'UNAIR - Universitas Airlangga']);
        DB::table('universities')->insert(['name' => 'UNSRI - Universitas Sriwijaya']);
        DB::table('universities')->insert(['name' => 'UBD - Universitas Bina Darma']);
        DB::table('universities')->insert(['name' => 'UK Petra - Universitas Kristen Petra']);

        // Add Event List
        DB::table('events')->insert(['name' => 'Business-IT Case Competition', 'price' => 300000, 'opened' => 1, 'date' => '2020-12-02 23:59:59', 'team_members' => 2, 'totp_key' => rand(100000, 999999)]);
        DB::table('events')->insert(['name' => 'Mobile Application Development Competition', 'price' => 300000, 'opened' => 1, 'date' => '2020-12-02 23:59:59', 'team_members' => 2, 'totp_key' => rand(100000, 999999)]);
        DB::table('events')->insert(['name' => 'Mini E-Sport: Mobile Legends', 'price' => 50000, 'opened' => 0, 'date' => '2020-10-03 00:00:00', 'team_members' => 5, 'team_members_reserve' => 1, 'slots' => 2, 'totp_key' => rand(100000, 999999)]);
        DB::table('events')->insert(['name' => 'Mini E-Sport: PUBG Mobile', 'price' => 20000, 'opened' => 0, 'date' => '2020-10-03 00:00:00', 'team_members' => 5, 'team_members_reserve' => 1, 'slots' => 2, 'totp_key' => rand(100000, 999999)]);
        DB::table('events')->insert(['name' => 'Mini E-Sport: Valorant', 'price' => 35000, 'opened' => 0, 'date' => '2020-10-03 00:00:00', 'team_members' => 5, 'team_members_reserve' => 1, 'slots' => 2, 'totp_key' => rand(100000, 999999)]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove all universities except the four former ones
        DB::table('universities')->where('id', '>', '4')->delete();
        DB::raw('ALTER TABLE `universities` (AUTO_INCREMENT = 5);');
        // Remove all events
        DB::table('events')->where('id', '>', '0')->delete();
        DB::raw('ALTER TABLE `events` (AUTO_INCREMENT = 1);');
    }
}
