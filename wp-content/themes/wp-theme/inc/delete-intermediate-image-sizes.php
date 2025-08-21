<?php
add_filter( 'intermediate_image_sizes', 'delete_intermediate_image_sizes' );


/**
 * Удаляет указанные размеры миниатюр.
 *
 * @param array $sizes Размеры.
 */
function delete_intermediate_image_sizes( $sizes ): array {
    // размеры которые нужно удалить.
    return array_diff(
        $sizes,
        [
            'thumbnail',
            'medium',
            'medium_large',
            'large',
            '1536x1536',
            '2048x2048',
        ]
    );
}