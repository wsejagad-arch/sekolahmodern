    try {
        const ai = new GoogleGenAI({ apiKey: apiKey });
        const response = await ai.models.generateContent({
            model: "gemini-3-flash-preview",
            contents: prompt
        });
        
        const text = response.text;
        const html = marked.parse(text);
        
        const fullHtml = `
            <div class="kop-surat">
                <h2>${type === 'modul' ? 'MODUL AJAR KURIKULUM MERDEKA' : 'ALUR TUJUAN PEMBELAJARAN (ATP)'}</h2>
                <h2>${namaSekolah}</h2>
                <p>Tahun Ajaran: ${ta}</p>
            </div>
            <div class="perangkat-body">${html}</div>
        `;
        $('#perangkat-preview').html(fullHtml);
        
        $('#ai-perangkat-status-text').text('Menyimpan ke folder kelas...');
        
        // Save to DB
        $.post('?ajax=perangkat_save_ai', {
            kelas: kelas,
            tipe: type,
            label: materi,
            html: fullHtml
        }, function(res) {
            if(res.status === 'success') {
                $('#ai-perangkat-result').removeClass('d-none');
                loadPerangkatDrive(); // refresh the drive
            } else {
                alert('Gagal menyimpan file: ' + res.message);
            }
        }, 'json').fail(function() {
            alert('Terjadi kesalahan saat menyimpan file perangkat ajar.');
        }).always(function() {
            $('#ai-perangkat-loading').addClass('d-none');
        });
        
    } catch (err) {
        alert('Gagal generate AI: ' + err.message);
        $('#ai-perangkat-loading').addClass('d-none');
    }
}
