<section class="philosophy-section" id="about">
    <div class="philosophy-container">
        <div class="prism-line"></div>
        <h2 class="philosophy-headline">Refracting Ideas<br />Into Reality</h2>
        <p class="philosophy-subheading">
            At PRISM FLUX, we transform complex challenges into elegant solutions
            through the convergence of cutting-edge technology and visionary design.
        </p>
        <div class="philosophy-pillars">
            <?php foreach ($articles as $index => $article): ?>
            <div class="pillar" data-article-id="<?php echo $article['id']; ?>">
                <div><img src="./img/produit<?php echo $index + 1; ?>.png" alt="Produit <?php echo $index + 1; ?>"></div>
                <!-- Interaction: Likes -->
                <div class="like-container" style="display:flex; align-items:center; gap: 8px; margin-top: 15px;">
                    <button class="like-btn" aria-label="Like">❤️</button>
                    <span class="likes-count">0</span>
                </div>
                <!-- Interaction: Commentaires -->
                <div class="comment-section" style="margin-top: 20px;">
                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px; font-weight:bold; font-size: 14px; color:#ccc;">
                        <span class="comment-icon" style="font-size:18px; user-select:none;">💬</span>
                        <span class="comments-count">0</span> Commentaire(s)
                    </div>
                    <form class="add-comment-form" style="display:flex; gap: 5px;">
                        <input type="text" class="comment-input" placeholder="Écrire un commentaire..." style="flex:1; padding:6px 8px; border-radius: 5px; border:none; outline:none; font-size: 14px;" required>
                        <button type="submit" style="padding: 6px 10px; border:none; border-radius: 5px; background:#00ffff; color:#111; font-weight: bold; cursor:pointer;">Envoyer</button>
                    </form>
                    <ul class="comments-list" style="list-style:none; padding-left:0; margin-top:10px; max-height: 100px; overflow-y: auto; font-size: 13px; color: #ddd;"></ul>
                </div>
                <a href="https://example.com/<?php echo $article['id']; ?>" rel="nofollow" target="_blank" style="color: var(--accent-cyan); text-decoration: none">
                    <?php echo htmlspecialchars($article['title']); ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="philosophy-particles" id="particles"></div>
    </div>
</section>