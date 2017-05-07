<h1>PINBOARD POSTS</h1> 

<dl>
<?php foreach($posts as $post): ?>
    <dt><a href="<?= $post['href'] ?>"><?= $post['description'] ?></a></dt> 
    <dd><?= $post['extended'] ?></dd>
<?php endforeach; ?>
</dl>