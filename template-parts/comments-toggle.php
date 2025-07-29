<?php
$post_id = get_the_ID();
$comments = get_comments([
  'post_id' => $post_id,
  'number'  => 5,
  'status'  => 'approve',
]);
?>

<div class="comment-box-wrapper" style="margin-top: 20px;">
  <button class="toggle-comments-btn" data-post-id="<?php echo esc_attr($post_id); ?>" style="background: #1a1a1b; color: #d7dadc; border: 1px solid #343536; padding: 8px 12px; border-radius: 4px; font-size: 14px; cursor: pointer;">
    💬 <span class="comment-count"><?php echo get_comments_number(); ?></span> Comments
  </button>

  <div class="comments-container" id="comments-<?php echo esc_attr($post_id); ?>" style="display: none; margin-top: 15px; background: #f9f9f9; padding: 15px; border-radius: 8px;">
    
    <?php if ($comments): ?>
      <ul class="comment-list" style="list-style-type: none; padding-left: 0;">
        <?php wp_list_comments(['style' => 'ul'], $comments); ?>
      </ul>
    <?php else: ?>
      <p style="margin-bottom: 10px;">No comments yet. Be the first to comment!</p>
    <?php endif; ?>

    <?php
    comment_form([
      'title_reply' => 'Add a comment',
      'comment_field' => '<p class="comment-form-comment">
        <textarea id="comment" name="comment" cols="45" rows="4" required placeholder="Write your comment here..." style="width:100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
      </p>',
    ]);
    ?>

    <a href="<?php the_permalink(); ?>#comments" class="show-more-comments" style="display: inline-block; margin-top: 10px;">Show More Comments</a>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".toggle-comments-btn").forEach(button => {
      button.addEventListener("click", function () {
        const postId = button.getAttribute("data-post-id");
        const container = document.getElementById("comments-" + postId);
        if (container) {
          container.style.display = container.style.display === "none" ? "block" : "none";
        }
      });
    });
  });
</script>
