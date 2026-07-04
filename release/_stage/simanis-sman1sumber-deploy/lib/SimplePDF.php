<?php
// Simple HTML to PDF converter using browser print CSS
class SimplePDF {
    
    public static function generatePDF($html_content, $filename = 'document.pdf') {
        // Clean the HTML content
        $clean_html = self::cleanHTML($html_content);
        
        // Add PDF-specific styles
        $pdf_html = self::addPDFStyles($clean_html);
        
        // Set headers for PDF output
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        
        return $pdf_html;
    }
    
    private static function cleanHTML($html) {
        // Remove unwanted elements for PDF
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        
        return $html;
    }
    
    private static function addPDFStyles($html) {
        $pdf_styles = '
        <style>
            @media print {
                @page {
                    size: A4;
                    margin: 0.5in;
                }
                body {
                    font-family: Arial, sans-serif;
                    font-size: 11px;
                    line-height: 1.4;
                    color: #000;
                    background: #fff;
                }
                .no-print {
                    display: none !important;
                }
                table {
                    page-break-inside: avoid;
                    border-collapse: collapse;
                    width: 100%;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 4px;
                    font-size: 10px;
                }
                th {
                    background-color: #f0f0f0 !important;
                    font-weight: bold;
                }
                .page-break {
                    page-break-before: always;
                }
            }
            
            /* General styles */
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                margin: 20px;
                background: #fff;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            .info {
                margin: 15px 0;
                padding: 10px;
                background: #f9f9f9;
                border-left: 4px solid #007bff;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th, td {
                border: 1px solid #333;
                padding: 8px;
                text-align: left;
                vertical-align: top;
            }
            th {
                background-color: #e9ecef;
                font-weight: bold;
                text-align: center;
            }
            .text-center {
                text-align: center;
            }
            .small {
                font-size: 10px;
            }
            .signature {
                margin-top: 40px;
                text-align: right;
            }
            .no-print {
                margin: 20px 0;
                text-align: center;
            }
            .btn {
                display: inline-block;
                padding: 8px 16px;
                margin: 5px;
                background: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                border: none;
                cursor: pointer;
            }
            .btn:hover {
                background: #0056b3;
            }
        </style>';
        
        // Insert styles into HTML head
        if (strpos($html, '</head>') !== false) {
            $html = str_replace('</head>', $pdf_styles . '</head>', $html);
        } else {
            $html = $pdf_styles . $html;
        }
        
        return $html;
    }
}
?>