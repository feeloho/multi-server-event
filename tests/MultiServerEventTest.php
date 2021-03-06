<?php

use Orchestra\Testbench\TestCase;

class MultiServerEventTest extends TestCase {

    /**
     * @test123
     */
    public function it_initializes_the_schedule()
    {
        $schedule = new Feeloho\MultiServerEvent\Scheduling\Schedule(app()[Illuminate\Contracts\Cache\Repository::class]);
        $this->assertTrue($schedule instanceof Feeloho\MultiServerEvent\Scheduling\Schedule);

        $result = $schedule->command('inspire')
            ->daily()
            ->withoutOverlappingMultiserver();
        dd($result);
    }

}