<?php
namespace Csgt\Utils\Console;

use Illuminate\Console\Command;

class MakeScaffoldCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:csgtscaffold';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make scaffold bootstrap groups';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $label = $this->ask('What is the field\'s label? (ex. Ship)');
        $field = $this->ask('What is the field name? (ex. ship.name)');
        $type  = $this->choice('What type of field do you need?', ['text', 'selectize']);

        $output = '<div class="form-group">
            <label class="control-label">' . $label . '</label>';

        switch ($type) {
            case 'selectize':
                $output .= '<selectize v-model="' . $field . '" :class="errorClass(\'' . $field . '\')">
                <option v-for="res in model" :value="res.id">{{ res.name }}</option>
            </selectize>';
                break;
            case 'text':
                $output .= '<input type="text" v-model="' . $field . '" :class="errorClass(\'' . $field . '\')">';
                break;
            default:
                # code...
                break;
        }

        $output .= '<div v-if="validationErrors[\'' . $field . '\']" class="invalid-feedback">
                {{ validationErrors[\'' . $field . '\'][0] }}
            </div>
        </div>';

        $this->info($output);
    }
}
