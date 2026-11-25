<!-- Export to Excel Customization Modal -->
<div class="modal fade" id="salesExportExcelModal" tabindex="-1" aria-labelledby="salesExportExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #443627;">
                <div class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                        <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                    </svg>
                    <h5 class="modal-title fw-bold m-0" id="salesExportExcelModalLabel">Export to Excel Customization</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="salesExportExcelForm" action="/admin/sales-report/export" method="GET">
                <!-- Hidden inputs synchronized with active Sales Reports filter -->
                <input type="hidden" name="format" value="xlsx">
                <input type="hidden" name="timeframe" id="exportTimeframeInput" value="today">
                <input type="hidden" name="year" id="exportYearInput" value="">
                <input type="hidden" name="month" id="exportMonthInput" value="">
                <input type="hidden" name="date" id="exportDateInput" value="">

                <div class="modal-body p-4">
                    <!-- Scope Banner -->
                    <div class="alert alert-light border d-flex justify-content-between align-items-center py-2 px-3 mb-3">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.72rem;">Current Reporting Scope</small>
                            <span id="exportModalScopeText" class="fw-bold" style="color: #443627;">Today</span>
                        </div>
                        <span class="badge bg-secondary rounded-pill px-2.5 py-1.5" id="exportModalFilterBadge">TODAY</span>
                    </div>

                    <!-- Preset Color Themes -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase mb-1.5">Color Palette Presets</label>
                        <div class="d-flex flex-wrap gap-1.5" id="excelThemePresets">
                            <button type="button" class="btn btn-sm btn-outline-secondary excel-preset-btn active"
                                data-header-bg="#443627" data-header-text="#FFFFFF" data-body-text="#212529" data-accent="#6A4435" data-row-bg="#F8F9FA" data-summary="#2E7D32">
                                Hotel Brand (Default)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary excel-preset-btn"
                                data-header-bg="#1E3A8A" data-header-text="#FFFFFF" data-body-text="#1F2937" data-accent="#3B82F6" data-row-bg="#EFF6FF" data-summary="#059669">
                                Corporate Navy
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary excel-preset-btn"
                                data-header-bg="#065F46" data-header-text="#FFFFFF" data-body-text="#111827" data-accent="#10B981" data-row-bg="#ECFDF5" data-summary="#047857">
                                Forest Emerald
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary excel-preset-btn"
                                data-header-bg="#18181B" data-header-text="#FFFFFF" data-body-text="#27272A" data-accent="#71717A" data-row-bg="#F4F4F5" data-summary="#4F46E5">
                                Modern Slate
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary excel-preset-btn"
                                data-header-bg="#78350F" data-header-text="#FFFFFF" data-body-text="#292524" data-accent="#D97706" data-row-bg="#FFFBEB" data-summary="#B45309">
                                Warm Amber
                            </button>
                        </div>
                    </div>

                    <!-- Color Customization Pickers Grid -->
                    <div class="row g-2 mb-3">
                        <!-- 1. Header Background Color -->
                        <div class="col-sm-6 col-md-4">
                            <label for="excelHeaderBg" class="form-label small fw-semibold text-dark mb-1">Header Background Color</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color p-1" id="excelHeaderBgPicker" value="#443627" title="Choose header background color">
                                <input type="text" class="form-control text-uppercase font-monospace" id="excelHeaderBg" name="header_bg" value="#443627" maxlength="7">
                            </div>
                        </div>

                        <!-- 2. Header Text Color -->
                        <div class="col-sm-6 col-md-4">
                            <label for="excelHeaderText" class="form-label small fw-semibold text-dark mb-1">Header Text Color</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color p-1" id="excelHeaderTextPicker" value="#FFFFFF" title="Choose header text color">
                                <input type="text" class="form-control text-uppercase font-monospace" id="excelHeaderText" name="header_text" value="#FFFFFF" maxlength="7">
                            </div>
                        </div>

                        <!-- 3. Body Text Color -->
                        <div class="col-sm-6 col-md-4">
                            <label for="excelBodyText" class="form-label small fw-semibold text-dark mb-1">Body Text Color</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color p-1" id="excelBodyTextPicker" value="#212529" title="Choose body text color">
                                <input type="text" class="form-control text-uppercase font-monospace" id="excelBodyText" name="body_text" value="#212529" maxlength="7">
                            </div>
                        </div>

                        <!-- 4. Primary Accent Color -->
                        <div class="col-sm-6 col-md-4">
                            <label for="excelAccentColor" class="form-label small fw-semibold text-dark mb-1">Primary Accent Color</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color p-1" id="excelAccentColorPicker" value="#6A4435" title="Choose accent color for headings">
                                <input type="text" class="form-control text-uppercase font-monospace" id="excelAccentColor" name="accent_color" value="#6A4435" maxlength="7">
                            </div>
                        </div>

                        <!-- 5. Row Background Colors (Alternating) -->
                        <div class="col-sm-6 col-md-4">
                            <label for="excelRowBg" class="form-label small fw-semibold text-dark mb-1">Row Background Colors</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color p-1" id="excelRowBgPicker" value="#F8F9FA" title="Choose alternating row color">
                                <input type="text" class="form-control text-uppercase font-monospace" id="excelRowBg" name="row_bg" value="#F8F9FA" maxlength="7">
                            </div>
                        </div>

                        <!-- 6. Summary/Total Color -->
                        <div class="col-sm-6 col-md-4">
                            <label for="excelSummaryColor" class="form-label small fw-semibold text-dark mb-1">Summary/Total Color</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color p-1" id="excelSummaryColorPicker" value="#2E7D32" title="Choose summary and total amount color">
                                <input type="text" class="form-control text-uppercase font-monospace" id="excelSummaryColor" name="summary_color" value="#2E7D32" maxlength="7">
                            </div>
                        </div>
                    </div>

                    <!-- Live Excel Table Preview -->
                    <div class="border rounded p-3 bg-light" id="excelPreviewContainer">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted fw-bold text-uppercase">Live Excel Styling Preview</span>
                            <small class="text-muted fst-italic">Applied across all sheets in the generated .xlsx workbook</small>
                        </div>

                        <div class="table-responsive border rounded bg-white" style="max-height: 240px;">
                            <table class="table table-sm m-0" id="excelPreviewTable" style="font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th colspan="4" id="prevSectionHeader" class="p-2 fw-bold" style="color: #6A4435; background-color: #ffffff; border-bottom: 2px solid #6A4435;">
                                            1. KEY FINANCIAL PERFORMANCE METRICS
                                        </th>
                                    </tr>
                                    <tr id="prevHeaderRow" style="background-color: #443627; color: #ffffff;">
                                        <th class="p-2 text-start" style="color: inherit;">Metric Description</th>
                                        <th class="p-2 text-center" style="color: inherit;">Category</th>
                                        <th class="p-2 text-end" style="color: inherit;">Value / Amount (PHP)</th>
                                        <th class="p-2 text-end" style="color: inherit;">Share (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="prevRow1" style="background-color: #ffffff; color: #212529;">
                                        <td class="p-2">Base Room Charges</td>
                                        <td class="p-2 text-center">Accommodation</td>
                                        <td class="p-2 text-end font-monospace">₱108,500.00</td>
                                        <td class="p-2 text-end">87.1%</td>
                                    </tr>
                                    <tr id="prevRow2" style="background-color: #F8F9FA; color: #212529;">
                                        <td class="p-2">Extra Charges &amp; Add-ons</td>
                                        <td class="p-2 text-center">Amenities</td>
                                        <td class="p-2 text-end font-monospace">₱16,000.00</td>
                                        <td class="p-2 text-end">12.9%</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr id="prevTotalRow" style="border-top: 2px solid #2E7D32; font-weight: bold; background-color: #F8F9FA;">
                                        <td class="p-2" id="prevTotalLabel" style="color: #2E7D32;">Total Gross Revenue</td>
                                        <td class="p-2 text-center" style="color: #2E7D32;">Settled</td>
                                        <td class="p-2 text-end font-monospace" id="prevTotalValue" style="color: #2E7D32;">₱124,500.00</td>
                                        <td class="p-2 text-end" style="color: #2E7D32;">100.0%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Worksheets Included Notice -->
                    <div class="mt-3 small text-muted d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#443627" viewBox="0 0 16 16">
                            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                        </svg>
                        <span>Includes 3 structured sheets: <strong>Sales Summary</strong>, <strong>Confirmed Payments</strong>, and <strong>Cancellations &amp; Pending</strong>.</span>
                    </div>
                </div>

                <div class="modal-footer border-0 py-2 px-3 d-flex justify-content-between align-items-center" style="background-color: #443627;">
                    <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold px-3" data-bs-dismiss="modal" style="color: #F5F7DF;">
                        Cancel
                    </button>
                    <button type="submit" id="confirmDownloadExcelBtn" class="btn btn-sm fw-bold px-4 py-2 d-inline-flex align-items-center gap-2" style="background-color: #F5F7DF; color: #443627; border-color: #F5F7DF;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#443627" viewBox="0 0 16 16">
                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                        </svg>
                        Download Excel (.xlsx)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
