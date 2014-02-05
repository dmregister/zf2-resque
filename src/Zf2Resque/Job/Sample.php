<?php

namespace Zf2Resque\Job;

class Sample
{
    public function setUp()
    {
        # Set up something before perform, like establishing a database connection
    }

    public function perform()
    {
        fwrite(STDOUT, 'Start job!');
        sleep(1);
        fwrite(STDOUT,'Job ended!' . PHP_EOL);
    }

    public function tearDown()
    {
        # Run after perform, like closing resources
    }

}