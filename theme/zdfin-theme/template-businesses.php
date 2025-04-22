<?php
/* Template name: Шаблон бизнес */
get_header();
?>
<div class="new-page">
    <div class="container">
        <h1 class="title"><?php echo esc_html(the_title('', '', false)); ?></h1>
        <div class="new-page__content">
            <?php the_content(); ?>
        </div>
        <div class="decor-list">
            <p class="decor-list__title">Мы помогаем:</p>
            <ol class="decor-list__items">
                <?php while (have_rows('businesses-list')):
                    the_row(); ?>
                    <li class="decor-list__item">
                        <span class="decor-list__text--bold"><?php the_sub_field('text-green'); ?></span>
                        <span class="decor-list__text--semibold"><?php the_sub_field('text-black'); ?></span>
                    </li>
                <?php endwhile; ?>
            </ol>
            <div class="decor-list__bot">
                <?php $descr = get_field('list-descr'); ?>
                <p class="decor-list__bot-descr"> <?php echo esc_html($descr); ?></p>
                <button class="decor-list__btn" data-modal="modal-1">Получить бесплатную консультацию</button>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>