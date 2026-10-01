<?php

test('the health endpoint responds successfully', function () {
    $response = $this->get('/up');

    $response->assertStatus(200);
});
