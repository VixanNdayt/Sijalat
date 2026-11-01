function editAlat(alat) {
    document.getElementById('formTitle').innerText = 'Edit Alat: ' + alat.nama;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('formAlat').action = '/admin/alat/' + alat.id;
    
    document.getElementById('inputNama').value = alat.nama;
    document.getElementById('inputCode').value = alat.code;
    document.getElementById('inputDeskripsi').value = alat.deskripsi;
    document.getElementById('inputBaik').value = alat.kondisi_baik;
    document.getElementById('inputRusak').value = alat.kondisi_rusak;
    document.getElementById('inputMtc').value = alat.kondisi_maintenance;
    
    document.getElementById('inputFoto').removeAttribute('required');
    document.getElementById('btnSubmit').innerText = 'Update Alat';
    document.getElementById('btnBatal').style.display = 'block';
    
    // Scroll otomatis ke atas (berguna jika di HP)
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function resetForm() {
    document.getElementById('formTitle').innerText = 'Tambah Alat Baru';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('formAlat').action = '{{ route("admin.alat.store") }}';
    
    document.getElementById('formAlat').reset();
    document.getElementById('inputFoto').setAttribute('required', 'required');
    
    document.getElementById('btnSubmit').innerText = 'Simpan Alat';
    document.getElementById('btnBatal').style.display = 'none';
}