<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('surveys:process-summaries')->daily();
