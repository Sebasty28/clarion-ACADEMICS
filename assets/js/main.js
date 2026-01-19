// assets/js/main.js

async function toggleLike(postId) {
  const btn = document.querySelector(`[data-like-btn="${postId}"]`);
  const countEl = document.querySelector(`[data-like-count="${postId}"]`);
  const likedCountEl = document.getElementById('likedCount');
  if (!btn) return;

  const icon = btn.querySelector('i');
  const wasLiked = btn.classList.contains('liked');

  btn.disabled = true;

  try {
    const form = new FormData();
    form.append("post_id", postId);

    const res = await fetch(`${window.CLARION_BASE_URL}/api/like_toggle.php`, {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.CLARION_CSRF_TOKEN || ""
      }
    });

    const data = await res.json();

    if (!data.ok) {
      alert(data.error || "Something went wrong.");
      return;
    }

    // Update UI based on server response
    if (data.liked) {
      btn.classList.add('liked');
      if (icon) {
        icon.className = 'bi bi-heart-fill';
      }
      showBookmarkToast('Post liked');
      if (likedCountEl) {
        likedCountEl.textContent = parseInt(likedCountEl.textContent || 0) + 1;
      }
    } else {
      btn.classList.remove('liked');
      if (icon) {
        icon.className = 'bi bi-heart';
      }
      showBookmarkToast('Post unliked');
      if (likedCountEl) {
        likedCountEl.textContent = Math.max(0, parseInt(likedCountEl.textContent || 0) - 1);
      }
    }

    if (countEl) {
      countEl.textContent = data.like_count;
    }
  } catch (e) {
    console.error('Like error:', e);
    alert("Network error. Try again.");
  } finally {
    btn.disabled = false;
  }
}

async function toggleBookmark(postId) {
  const btn = document.querySelector(`[data-bookmark-btn="${postId}"]`);
  const countEl = document.getElementById('bookmarkCount');
  if (!btn) return;

  const card = btn.closest('.col-md-6');
  const isDeleted = card?.dataset.deleted === '1';

  const icon = btn.querySelector('i');
  const wasBookmarked = btn.classList.contains('bookmarked') || isDeleted;
  
  // Show confirmation for both adding and removing
  const confirmed = wasBookmarked 
    ? await showBookmarkRemoveConfirm()
    : await showBookmarkConfirm();
  
  if (!confirmed) return;
  
  btn.disabled = true;

  try {
    const form = new FormData();
    form.append("post_id", postId);

    const res = await fetch(`${window.CLARION_BASE_URL}/api/bookmark_toggle.php`, {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.CLARION_CSRF_TOKEN || ""
      }
    });

    const data = await res.json();

    if (!data.ok) {
      alert(data.error || "Something went wrong.");
      return;
    }

    // Update UI based on server response
    if (data.bookmarked) {
      btn.classList.add('bookmarked');
      if (icon) {
        icon.className = 'bi bi-bookmark-fill';
      }
      showBookmarkToast('Added to saved items');
    } else {
      btn.classList.remove('bookmarked');
      if (icon) {
        icon.className = 'bi bi-bookmark';
      }
      showBookmarkToast('Removed from saved items');
    }

    if (countEl) {
      countEl.textContent = data.bookmark_count;
    }
  } catch (e) {
    console.error('Bookmark error:', e);
    alert("Network error. Try again.");
  } finally {
    btn.disabled = false;
  }
}

function showBookmarkConfirm() {
  return new Promise((resolve) => {
    const modal = document.createElement('div');
    modal.className = 'bookmark-modal';
    modal.innerHTML = `
      <div class="bookmark-modal-content">
        <i class="bi bi-bookmark-star" style="font-size:48px;color:#2E5FA8;margin-bottom:16px;"></i>
        <h3 style="font-family:'Inter',sans-serif;font-size:20px;font-weight:600;color:#0f172a;margin-bottom:8px;">Save this item?</h3>
        <p style="font-family:'Inter',sans-serif;font-size:14px;color:#697483;margin-bottom:24px;">Add this to your saved items for easy access later.</p>
        <div style="display:flex;gap:12px;">
          <button class="bookmark-modal-btn cancel" style="flex:1;padding:10px;border:1px solid #e5e7eb;background:white;color:#697483;border-radius:8px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;cursor:pointer;">Cancel</button>
          <button class="bookmark-modal-btn confirm" style="flex:1;padding:10px;border:none;background:#2E5FA8;color:white;border-radius:8px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;cursor:pointer;">Save</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    
    setTimeout(() => modal.classList.add('show'), 10);
    
    modal.querySelector('.cancel').onclick = () => {
      modal.classList.remove('show');
      setTimeout(() => modal.remove(), 300);
      resolve(false);
    };
    
    modal.querySelector('.confirm').onclick = () => {
      modal.classList.remove('show');
      setTimeout(() => modal.remove(), 300);
      resolve(true);
    };
    
    modal.onclick = (e) => {
      if (e.target === modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
        resolve(false);
      }
    };
  });
}

function showBookmarkRemoveConfirm() {
  return new Promise((resolve) => {
    const modal = document.createElement('div');
    modal.className = 'bookmark-modal';
    modal.innerHTML = `
      <div class="bookmark-modal-content">
        <i class="bi bi-bookmark-x" style="font-size:48px;color:#dc3545;margin-bottom:16px;"></i>
        <h3 style="font-family:'Inter',sans-serif;font-size:20px;font-weight:600;color:#0f172a;margin-bottom:8px;">Remove from saved?</h3>
        <p style="font-family:'Inter',sans-serif;font-size:14px;color:#697483;margin-bottom:24px;">This item will be removed from your saved items.</p>
        <div style="display:flex;gap:12px;">
          <button class="bookmark-modal-btn cancel" style="flex:1;padding:10px;border:1px solid #e5e7eb;background:white;color:#697483;border-radius:8px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;cursor:pointer;">Cancel</button>
          <button class="bookmark-modal-btn confirm" style="flex:1;padding:10px;border:none;background:#dc3545;color:white;border-radius:8px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;cursor:pointer;">Remove</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    
    setTimeout(() => modal.classList.add('show'), 10);
    
    modal.querySelector('.cancel').onclick = () => {
      modal.classList.remove('show');
      setTimeout(() => modal.remove(), 300);
      resolve(false);
    };
    
    modal.querySelector('.confirm').onclick = () => {
      modal.classList.remove('show');
      setTimeout(() => modal.remove(), 300);
      resolve(true);
    };
    
    modal.onclick = (e) => {
      if (e.target === modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
        resolve(false);
      }
    };
  });
}

function showBookmarkToast(message) {
  const container = document.getElementById('toastContainer') || createToastContainer();
  
  const toast = document.createElement('div');
  toast.className = 'bookmark-toast';
  toast.innerHTML = `
    <i class="bi bi-check-circle-fill me-2"></i>
    <span>${message}</span>
  `;
  container.appendChild(toast);
  
  setTimeout(() => toast.classList.add('show'), 10);
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 2000);
}

function createToastContainer() {
  const container = document.createElement('div');
  container.id = 'toastContainer';
  container.style.cssText = 'position:fixed;bottom:30px;right:30px;display:flex;flex-direction:column;gap:10px;z-index:9999;';
  document.body.appendChild(container);
  return container;
}
