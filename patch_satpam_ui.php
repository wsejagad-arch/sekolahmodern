<?php
$file = 'c:\xampp\htdocs\jurnal\satpam.php';
$content = file_get_contents($file);

$btnLogic = <<<HTML
                        <div class="mt-3">
                            <?php if (\$val['kategori_pengajuan'] === 'Keluar Sekolah'): ?>
                                <?php if (\$wakel_ok && \$val['validasi_satpam'] === 'Menunggu'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="validasi_satpam">
                                        <input type="hidden" name="id_izin" value="<?= \$val['id_izin'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm w-100 mb-2" onclick="return confirm('Beri izin keluar?')"><i class="fas fa-sign-out-alt"></i> Validasi Keluar</button>
                                    </form>
                                <?php elseif (\$val['validasi_satpam'] === 'Disetujui' && \$val['opsi_kembali'] === 'Kembali ke Sekolah' && empty(\$val['waktu_kembali'])): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="satpam_masuk">
                                        <input type="hidden" name="id_izin" value="<?= \$val['id_izin'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm w-100 mb-2" onclick="return confirm('Konfirmasi siswa masuk kembali?')"><i class="fas fa-sign-in-alt"></i> Masuk Lagi</button>
                                    </form>
                                <?php elseif (!\$wakel_ok && \$val['validasi_satpam'] === 'Menunggu'): ?>
                                    <button class="btn btn-secondary btn-sm w-100 mb-2" disabled><i class="fas fa-lock"></i> Menunggu Wali Kelas</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
HTML;

$content = str_replace('</div>
                    </div>
                </div>
                <?php endforeach; ?>', $btnLogic . '</div>
                    </div>
                </div>
                <?php endforeach; ?>', $content);

file_put_contents($file, $content);
echo "Buttons added!";
?>
