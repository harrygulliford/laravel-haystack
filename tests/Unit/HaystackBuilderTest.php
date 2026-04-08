<?php

declare(strict_types=1);

use Sammyjo20\LaravelHaystack\Models\Haystack;
use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Builders\HaystackBuilder;
use Sammyjo20\LaravelHaystack\Data\PendingHaystackBale;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\Middleware;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\InvokableClass;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\InvokableMiddleware;

test('you can add jobs to the haystack builder', function () {
    $builder = new HaystackBuilder;

    expect($builder->getJobs())->toHaveCount(0);

    $samJob = new NameJob('Sam');
    $garethJob = new NameJob('Gareth');

    $builder->addJob($samJob);
    $builder->addBale($garethJob);

    $jobs = $builder->getJobs();

    expect($jobs)->toHaveCount(2);
    expect($jobs[0])->toBeInstanceOf(PendingHaystackBale::class);
    expect($jobs[1])->toBeInstanceOf(PendingHaystackBale::class);

    $samPendingJob = $jobs[0];
    $garethPendingJob = $jobs[1];

    expect($samPendingJob->job)->toEqual($samJob);
    expect($samPendingJob->delayInSeconds)->toEqual(0);
    expect($samPendingJob->queue)->toBeNull();
    expect($samPendingJob->connection)->toBeNull();

    expect($garethPendingJob->job)->toEqual($garethJob);
    expect($garethPendingJob->delayInSeconds)->toEqual(0);
    expect($garethPendingJob->queue)->toBeNull();
    expect($garethPendingJob->connection)->toBeNull();
});

test('you can specify a global timeout, queue and connection on the builder for all jobs', function () {
    $builder = new HaystackBuilder;

    $builder->withDelay(60);
    $builder->onConnection('database');
    $builder->onQueue('testing');

    expect($builder->getGlobalDelayInSeconds())->toEqual(60);
    expect($builder->getGlobalConnection())->toEqual('database');
    expect($builder->getGlobalQueue())->toEqual('testing');
});

test('you can specify a closure or a callable to happen at the end of a successful haystack and it will chain functions', function () {
    $builder = new HaystackBuilder;

    $hello = fn () => 'Hello';

    $builder->then($hello);

    expect($builder->getCallbacks()->onThen)->toEqual([
        new SerializableClosure($hello),
    ]);

    $builder->then(new InvokableClass);

    $onThen = $builder->getCallbacks()->onThen;

    expect($onThen)->toHaveCount(2);
    expect($onThen[0])->toEqual(new SerializableClosure($hello));
    expect($onThen[1])->toBeInstanceOf(SerializableClosure::class);
    expect(($onThen[1])())->toBe('Howdy!');
});

test('you can specify a closure to happen at the end of any haystack', function () {
    $builder = new HaystackBuilder;

    $hello = fn () => 'Hello';

    $builder->finally($hello);

    expect($builder->getCallbacks()->onFinally)->toEqual([
        new SerializableClosure($hello),
    ]);

    $builder->finally(new InvokableClass);

    $onFinally = $builder->getCallbacks()->onFinally;

    expect($onFinally)->toHaveCount(2);
    expect($onFinally[0])->toEqual(new SerializableClosure($hello));
    expect($onFinally[1])->toBeInstanceOf(SerializableClosure::class);
    expect(($onFinally[1])())->toBe('Howdy!');
});

test('you can specify a closure to happen on an erroneous haystack', function () {
    $builder = new HaystackBuilder;

    $hello = fn () => 'Hello';

    $builder->catch($hello);

    expect($builder->getCallbacks()->onCatch)->toEqual([
        new SerializableClosure($hello),
    ]);

    $builder->catch(new InvokableClass);

    $onCatch = $builder->getCallbacks()->onCatch;

    expect($onCatch)->toHaveCount(2);
    expect($onCatch[0])->toEqual(new SerializableClosure($hello));
    expect($onCatch[1])->toBeInstanceOf(SerializableClosure::class);
    expect(($onCatch[1])())->toBe('Howdy!');
});

test('you can specify a closure to happen on a paused haystack', function () {
    $builder = new HaystackBuilder;

    $hello = fn () => 'Hello';

    $builder->paused($hello);

    expect($builder->getCallbacks()->onPaused)->toEqual([
        new SerializableClosure($hello),
    ]);

    $builder->paused(new InvokableClass);

    $onPaused = $builder->getCallbacks()->onPaused;

    expect($onPaused)->toHaveCount(2);
    expect($onPaused[0])->toEqual(new SerializableClosure($hello));
    expect($onPaused[1])->toBeInstanceOf(SerializableClosure::class);
    expect(($onPaused[1])())->toBe('Howdy!');
});

test('you can specify middleware as a closure, invokable class or an array', function () {
    $builder = new HaystackBuilder;

    $returnsMiddlewareArray = fn () => [new Middleware];

    $builder->addMiddleware($returnsMiddlewareArray);

    expect($builder->getMiddleware()->data)->toEqual([
        new SerializableClosure($returnsMiddlewareArray),
    ]);

    $builder->addMiddleware(new InvokableMiddleware);

    $data = $builder->getMiddleware()->data;

    expect($data)->toHaveCount(2);
    expect($data[0])->toEqual(new SerializableClosure($returnsMiddlewareArray));
    expect($data[1])->toBeInstanceOf(SerializableClosure::class);
    $invokableResult = ($data[1])();
    expect($invokableResult)->toHaveCount(1);
    expect($invokableResult[0])->toBeInstanceOf(Middleware::class);

    $preset = [new Middleware];
    $builder->addMiddleware($preset);

    $data = $builder->getMiddleware()->data;

    expect($data)->toHaveCount(3);
    expect($data[0])->toEqual(new SerializableClosure($returnsMiddlewareArray));
    expect($data[1])->toBeInstanceOf(SerializableClosure::class);
    expect(($data[2])())->toBe($preset);

    // Now we'll try to get all the middleware, it should give us a nice array of them all

    $allMiddleware = $builder->getMiddleware()->toMiddlewareArray();

    expect($allMiddleware)->toHaveCount(3);
    expect($allMiddleware[0])->toBeInstanceOf(Middleware::class);
    expect($allMiddleware[1])->toBeInstanceOf(Middleware::class);
    expect($allMiddleware[2])->toBeInstanceOf(Middleware::class);
});

test('you can create a haystack from a builder', function () {
    $builder = new HaystackBuilder;

    $haystack = $builder->create();

    expect($haystack)->toBeInstanceOf(Haystack::class);
});

test('you can specify a custom delay, connection or queue on a per job basis', function () {
    $builder = new HaystackBuilder;

    $builder->addJob(new NameJob('Sam'), 60, 'testing', 'database');

    $jobs = $builder->getJobs();

    expect($jobs[0]->delayInSeconds)->toEqual(60);
    expect($jobs[0]->queue)->toEqual('testing');
    expect($jobs[0]->connection)->toEqual('database');
});

test('you can specify a custom delay, connection or queue on a per job basis which takes priority over globals', function () {
    $builder = new HaystackBuilder;

    $builder->withDelay(120);
    $builder->onQueue('cowboy');
    $builder->onConnection('redis');

    $builder->addJob(new NameJob('Sam'), 60, 'testing', 'database');

    $jobs = $builder->getJobs();

    expect($jobs[0]->delayInSeconds)->toEqual(60);
    expect($jobs[0]->queue)->toEqual('testing');
    expect($jobs[0]->connection)->toEqual('database');
});

test('it will respect the delay, connection and queue added to jobs if not set', function () {
    $builder = new HaystackBuilder;

    $builder->withDelay(120);
    $builder->onQueue('cowboy');
    $builder->onConnection('redis');

    $job = new NameJob('Sam');

    $job->delay(60);
    $job->onConnection('database');
    $job->onQueue('testing');

    $builder->addJob($job);

    $jobs = $builder->getJobs();

    expect($jobs[0]->delayInSeconds)->toEqual(60);
    expect($jobs[0]->queue)->toEqual('testing');
    expect($jobs[0]->connection)->toEqual('database');
});

test('job specified delay, connection or queue on a per job basis which takes priority over globals', function () {
    $builder = new HaystackBuilder;

    $builder->withDelay(120);
    $builder->onQueue('cowboy');
    $builder->onConnection('redis');

    $job = new NameJob('Sam');

    $job->delay(60);
    $job->onConnection('database');
    $job->onQueue('testing');

    $builder->addJob($job);

    $jobs = $builder->getJobs();

    expect($jobs[0]->delayInSeconds)->toEqual(60);
    expect($jobs[0]->queue)->toEqual('testing');
    expect($jobs[0]->connection)->toEqual('database');
});

test('you can dispatch a haystack right away', function () {
    $builder = new HaystackBuilder;

    $haystack = $builder->dispatch();

    expect($haystack->started)->toBeTrue();
});

test('you can use conditional clauses when building your haystack', function () {
    $builder = new HaystackBuilder;
    $neilJob = new NameJob('Neil');

    $builder->when(true, function ($haystack) use ($neilJob) {
        $haystack->addJob($neilJob);
    })->when(false, function ($haystack) {
        $haystack->withDelay(30);
    }, function ($haystack) {
        $haystack->withDelay(50);
    })->when(true, function ($haystack) {
        $haystack->onConnection('database');
    });

    $jobs = $builder->getJobs();

    expect($jobs)->toHaveCount(1);
    expect($jobs[0]->job)->toEqual($neilJob);

    expect($builder->getGlobalDelayInSeconds())->toEqual(50);
    expect($builder->getGlobalConnection())->toEqual('database');
});
