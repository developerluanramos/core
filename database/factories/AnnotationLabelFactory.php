<?php

use Faker\Generator as Faker;

$factory->define(Biigle\AnnotationLabel::class, function (Faker $faker) {
    return [
        'annotation_id' => function () {
            return factory(Biigle\Annotation::class)->create()->id;
        },
        'label_id' => function () {
            return factory(Biigle\Label::class)->create()->id;
        },
        'user_id' => function () {
            return factory(Biigle\User::class)->create()->id;
        },
        'confidence' => $faker->randomFloat(null, 0, 1),
    ];
});
