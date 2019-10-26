<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\Score;

class CreateScoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tournament_id')->unsigned();
            $table->string('team_top');
            $table->string('team_bot');
            $table->tinyInteger('score_top');
            $table->tinyInteger('score_bot');
            $table->enum('current_bracket', [Score::BRACKET_TYPE_WINNERS, Score::BRACKET_TYPE_LOSERS]);
            $table->enum('previous_bracket', [Score::BRACKET_TYPE_WINNERS, Score::BRACKET_TYPE_LOSERS]);
            $table->string('round');

            $table->timestamps();
        });
        Schema::table('scores', function (Blueprint $table) {
            $table->foreign('tournament_id')->references('id')->on('tournaments')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scores');
    }
}
