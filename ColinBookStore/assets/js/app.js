/* Script global ColinBookStore: tema, validasi interaksi, upload preview, dan transisi. */
document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  // Satu key membuat preferensi tema customer dan admin tetap konsisten.
  const darkModeKey = 'colinbookstore-dark-mode';

  // Terapkan class tema, simpan preferensi, dan sinkronkan status tombol aksesibel.
  const setDarkMode = enabled => {
    body.classList.toggle('dark-mode', enabled);
    try {
      localStorage.setItem(darkModeKey, enabled ? 'true' : 'false');
    } catch (error) {
      // The page remains usable when localStorage is unavailable.
    }
    document.querySelectorAll('[data-dark-mode-toggle]').forEach(toggle => {
      toggle.setAttribute('aria-pressed', String(enabled));
    });
    document.querySelectorAll('[data-dark-mode-label]').forEach(label => {
      label.textContent = enabled ? 'ON' : 'OFF';
    });
  };

  // Baca tema terakhir; fallback terang jika localStorage diblokir browser.
  let savedDarkMode = false;
  try {
    savedDarkMode = localStorage.getItem(darkModeKey) === 'true';
  } catch (error) {
    savedDarkMode = false;
  }
  setDarkMode(savedDarkMode);

  // Setiap tombol toggle memakai handler yang sama di customer dan admin.
  document.querySelectorAll('[data-dark-mode-toggle]').forEach(toggle => {
    toggle.addEventListener('click', () => {
      setDarkMode(!body.classList.contains('dark-mode'));
    });
  });

  // Konfirmasi mencegah aksi destruktif terjadi tanpa persetujuan user.
  document.querySelectorAll('[data-confirm]').forEach(element => {
    element.addEventListener('click', event => {
      if (!window.confirm(element.dataset.confirm || 'Lanjutkan tindakan ini?')) {
        event.preventDefault();
      }
    });
  });

  // Filter admin/customer tertentu dapat submit otomatis setelah berubah.
  document.querySelectorAll('[data-auto-submit]').forEach(element => {
    element.addEventListener('change', () => {
      if (element.form) {
        element.form.submit();
      }
    });
  });

  // Tombol copy dipakai untuk nomor rekening dan memberi feedback singkat.
  document.querySelectorAll('[data-copy-value]').forEach(button => {
    button.addEventListener('click', async () => {
      const originalLabel = button.textContent;
      const value = button.dataset.copyValue || '';
      try {
        await navigator.clipboard.writeText(value);
        button.textContent = 'Tersalin ✓';
      } catch (error) {
        button.textContent = value;
      }
      window.setTimeout(() => {
        button.textContent = originalLabel;
      }, 1800);
    });
  });

  // Checkout transfer/COD dikendalikan tanpa reload melalui data attributes.
  const paymentForm = document.querySelector('[data-payment-form]');
  if (paymentForm) {
    const details = paymentForm.querySelector('[data-transfer-details]');
    const proof = paymentForm.querySelector('[data-payment-proof]');
    const fileName = paymentForm.querySelector('[data-upload-file-name]');
    const preview = paymentForm.querySelector('[data-upload-preview]');
    const cashFields = paymentForm.querySelector('[data-cash-payment]');
    const paidInput = paymentForm.querySelector('[data-cash-paid]');
    const changeOutput = paymentForm.querySelector('[data-cash-change]');
    const methods = paymentForm.querySelectorAll('[name="metode_pembayaran"]');
    const orderTotal = Number(paymentForm.dataset.orderTotal || 0);

    // Format kembalian ke rupiah tanpa mengirim data ke server sebelum submit.
    const formatRupiah = value => new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(Math.max(0, value));

    // Kembalian dihitung live agar pembeli dapat memeriksa nominal sebelum submit.
    const updateCashChange = () => {
      const paid = Number(paidInput?.value || 0);
      const enoughCash = paid >= orderTotal;
      if (changeOutput) {
        changeOutput.textContent = formatRupiah(enoughCash ? paid - orderTotal : 0);
      }
      if (paidInput) {
        paidInput.setCustomValidity(
          paidInput.value !== '' && !enoughCash
            ? 'Uang pembeli kurang dari total pembayaran.'
            : ''
        );
      }
    };

    // Sinkronkan field bukti transfer dengan metode pembayaran terpilih.
    const updatePaymentFields = () => {
      const selected = paymentForm.querySelector('[name="metode_pembayaran"]:checked');
      const transferSelected = selected && selected.value === 'TRANSFER';
      if (cashFields) {
        cashFields.hidden = Boolean(transferSelected);
      }
      if (paidInput) {
        paidInput.disabled = Boolean(transferSelected);
        paidInput.required = !transferSelected;
        if (transferSelected) {
          paidInput.value = '';
          paidInput.setCustomValidity('');
        }
      }
      if (details) {
        details.hidden = !transferSelected;
      }
      if (proof) {
        proof.disabled = !transferSelected;
        proof.required = Boolean(transferSelected);
        if (!transferSelected) {
          proof.value = '';
          if (fileName) fileName.textContent = '';
          if (preview) {
            preview.hidden = true;
            preview.removeAttribute('src');
          }
        }
      }
      updateCashChange();
    };

    methods.forEach(method => method.addEventListener('change', updatePaymentFields));
    if (paidInput) {
      paidInput.addEventListener('input', updateCashChange);
    }
    // Validasi client-side memberi feedback format, ukuran, dan preview gambar.
    if (proof) {
      proof.addEventListener('change', () => {
        const file = proof.files && proof.files[0];
        if (!file) return;
        const allowed = ['image/jpeg', 'image/png', 'application/pdf'];
        const maxSize = 2 * 1024 * 1024;
        if (!allowed.includes(file.type)) {
          proof.setCustomValidity('Gunakan JPG, JPEG, PNG, atau PDF.');
          if (fileName) fileName.textContent = 'Format file tidak diizinkan.';
          if (preview) preview.hidden = true;
          return;
        }
        if (file.size > maxSize) {
          proof.setCustomValidity('Ukuran maksimal 2 MB.');
          if (fileName) fileName.textContent = 'File terlalu besar. Maksimal 2 MB.';
          if (preview) preview.hidden = true;
          return;
        }
        proof.setCustomValidity('');
        if (fileName) fileName.textContent = `Dipilih: ${file.name} (${Math.ceil(file.size / 1024)} KB)`;
        if (preview && file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.addEventListener('load', event => {
            preview.src = event.target.result;
            preview.hidden = false;
          });
          reader.readAsDataURL(file);
        } else if (preview) {
          preview.hidden = true;
        }
      });
    }
    updatePaymentFields();
    updateCashChange();
  }

  // Transisi link hanya aktif jika user tidak meminta reduced motion.
  const page = document.querySelector('.page-transition');
  if (page && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('a[href]').forEach(link => {
      link.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target === '_blank' || link.hasAttribute('download') || link.closest('.rail, .bottom-nav, .profile-menu')) return;
        let destination;
        try {
          destination = new URL(link.href, window.location.href);
        } catch (error) {
          return;
        }
        if (destination.origin !== window.location.origin || destination.href === window.location.href) return;
        event.preventDefault();
        page.classList.add('page-leaving');
        window.setTimeout(() => { window.location.href = destination.href; }, 170);
      });
    });
  }
});
