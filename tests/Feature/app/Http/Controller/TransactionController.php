<?php

namespace Tests\Feature\app\Http\Controller;

use App\Models\Retailer;
use App\Models\User;
use Illuminate\Notifications\SendQueuedNotifications;
use Tests\TestCase;

class TransactionController extends TestCase
{
    public function testUserShouldBeWrongProvider()
    {
        //dados do usuarios
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 3|WThJZIKUClKc3kbMJHofkEsAJCLpu0rnAOr7d5eO96cb85de'
        ];

        $user = User::where('email', 'josepedro@gmail.com')->first();

        $payload = [
            'provider' => 'nada',
            'payee_id' => 'nada',
            'amount' => 123
        ];

        $request = $this->actingAs($user, 'users')
            ->postJson(route('postTransaction'), $payload, $headers);

        $request->assertStatus(422);

        $request->assertJson(["message" => 'The selected provider is invalid.',
            'errors' => ['provider' =>[ "The selected provider is invalid."]]]);

    }
    public function testUserShouldBeExistingOnProviderToTransfer()
    {
        //dados do usuarios
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 3|WThJZIKUClKc3kbMJHofkEsAJCLpu0rnAOr7d5eO96cb85de'
        ];

        $user = User::where('email', 'josepedro@gmail.com')->first();

        $payload = [
            'provider' => 'user',
            'payee_id' => 'nada',
            'amount' => 123
        ];

        $request = $this->actingAs($user, 'users')
                        ->postJson(route('postTransaction'), $payload, $headers);

        $request->assertStatus(404);

    }

    public function testUserShouldBeAValidUserToTransfer()
    {
        //dados do usuarios
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 3|WThJZIKUClKc3kbMJHofkEsAJCLpu0rnAOr7d5eO96cb85de'
        ];

        $user = User::where('email', 'josepedro@gmail.com')->first();

        $payload = [
            'provider' => 'user',
            'payee_id' => 'nada',
            'amount' => 123
        ];

        $request = $this->actingAs($user, 'users')
            ->postJson(route('postTransaction'), $payload, $headers);

        $request->assertStatus(404);

    }

    public function testRetailerShouldNotTransfer()
    {

        //dados do usuarios
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 2|4BSW39eryOm4bZ1XoqbdftUxv7UMXtMPRICgQ1ji6de00a55'
        ];

        $user = Retailer::where('email', 'jkuhlman@example.com')->first();

        $payload = [
            'provider' => 'user',
            'payee_id' => 'nada',
            'amount' => 123
        ];

        $request = $this->actingAs($user, 'retailers')
            ->postJson(route('postTransaction'), $payload, $headers);

        $request->assertStatus(401);
        $request->assertJson(['errors' => ['main' => "Retailer is not authorized to make transactions"]]);
    }

    public function testUserShouldHaveMoneyToPerformSomeTransaction()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 3|WThJZIKUClKc3kbMJHofkEsAJCLpu0rnAOr7d5eO96cb85de'
        ];

        $user = User::where('email', 'josepedro@gmail.com')->first();

        $payload = [
            'provider' => 'user',
            'payee_id' => 1,
            'amount' => 123
        ];

        $request = $this->actingAs($user, 'users')
            ->postJson(route('postTransaction'), $payload, $headers);

        $request->assertStatus(422);
        $request->assertJson(['errors' => ['main' => "balance in the card is not enough"]]);
    }

    public function testUserCanTransferMoney()
    {
        $this->expectsEvents(SendQueuedNotifications::class);

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 3|WThJZIKUClKc3kbMJHofkEsAJCLpu0rnAOr7d5eO96cb85de'
        ];

        $userPayer = User::where('email', 'josepedro@gmail.com')->first();
        $userPayer->wallet->deposit(1000);
        $userPayed = User::factory()->create();

        $payload = [
            'provider' => 'users',
            'payee_id' => $userPayed->id,
            'amount' => 100
        ];
        $request = $this->actingAs($userPayer, 'users')
            ->post(route('postTransaction'), $payload);


        $request->assertResponseStatus(200);

        $request->seeInDatabase('wallets', [
            'id' => $userPayer->wallet->id,
            'balance' => 900
        ]);

        $request->seeInDatabase('wallets', [
            'id' => $userPayed->wallet->id,
            'balance' => 100
        ]);
    }
}