 let currentStatusFilter = 'all';

        function searchAttendance() {
            const position = document.getElementById('positionFilter').value.toLowerCase();
            const department = document.getElementById('departmentFilter').value.toLowerCase();
            
            
            const rows = document.querySelectorAll('#attendanceTableBody tr');
            rows.forEach(row => {
                const pos = row.cells[2].textContent.toLowerCase();
                const dept = row.cells[3].textContent.toLowerCase();
                const status = row.dataset.status;

                const matchesPosition = position === '' || pos.includes(position);
                const matchesDept = department === '' || dept.includes(department);
                const matchesStatus = currentStatusFilter === 'all' || status === currentStatusFilter;

                row.style.display = (matchesPosition && matchesDept && matchesStatus) ? '' : 'none';
            });

            
            const cards = document.querySelectorAll('.attendance-card');
            cards.forEach(card => {
                const pos = card.dataset.position;
                const dept = card.dataset.department;
                const status = card.dataset.status;

                const matchesPosition = position === '' || pos.includes(position);
                const matchesDept = department === '' || dept.includes(department);
                const matchesStatus = currentStatusFilter === 'all' || status === currentStatusFilter;

                card.style.display = (matchesPosition && matchesDept && matchesStatus) ? '' : 'none';
            });
        }

        function filterByStatus(status) {
            currentStatusFilter = status;
            searchAttendance();
        }

        function exportAttendance() {
            
            const today = new Date();
            const dateStr = today.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            
            
            const rows = document.querySelectorAll('#attendanceTableBody tr');
            let visibleRecords = [];
            let presentCount = 0, absentCount = 0, leaveCount = 0;
            
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const status = row.dataset.status;
                    const record = {
                        id: row.cells[0].textContent,
                        name: row.cells[1].textContent,
                        position: row.cells[2].textContent,
                        department: row.cells[3].textContent,
                        timeIn: row.cells[4].textContent,
                        timeOut: row.cells[5].textContent,
                        status: status
                    };
                    visibleRecords.push(record);
                    
                    if (status === 'Present') presentCount++;
                    else if (status === 'Absent') absentCount++;
                    else if (status === 'Leave') leaveCount++;
                }
            });

            
            const printWindow = window.open('', '', 'width=800,height=600');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Attendance Report - ${dateStr}</title>
                    <style>
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        body {
                            font-family: Arial, sans-serif;
                            padding: 30px;
                            color: #333;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 30px;
                            border-bottom: 3px solid #0d9488;
                            padding-bottom: 20px;
                        }
                        .header h1 {
                            color: #0d9488;
                            font-size: 28px;
                            margin-bottom: 5px;
                        }
                        .header p {
                            color: #666;
                            font-size: 14px;
                        }
                        .summary {
                            display: flex;
                            justify-content: space-around;
                            margin-bottom: 30px;
                            background: #f3f4f6;
                            padding: 20px;
                            border-radius: 8px;
                        }
                        .summary-item {
                            text-align: center;
                        }
                        .summary-item h3 {
                            font-size: 14px;
                            color: #666;
                            margin-bottom: 8px;
                            text-transform: uppercase;
                            letter-spacing: 1px;
                        }
                        .summary-item p {
                            font-size: 32px;
                            font-weight: bold;
                            color: #0d9488;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 30px;
                        }
                        thead {
                            background: #0d9488;
                            color: white;
                        }
                        th, td {
                            padding: 12px;
                            text-align: left;
                            border: 1px solid #ddd;
                            font-size: 12px;
                        }
                        th {
                            font-weight: 600;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            font-size: 11px;
                        }
                        tbody tr:nth-child(even) {
                            background: #f9fafb;
                        }
                        tbody tr:hover {
                            background: #f3f4f6;
                        }
                        .status-badge {
                            padding: 4px 10px;
                            border-radius: 12px;
                            font-size: 11px;
                            font-weight: 600;
                            display: inline-block;
                        }
                        .status-present {
                            background: #d1fae5;
                            color: #065f46;
                        }
                        .status-absent {
                            background: #fee2e2;
                            color: #991b1b;
                        }
                        .status-leave {
                            background: #fef3c7;
                            color: #92400e;
                        }
                        .footer {
                            margin-top: 40px;
                            padding-top: 20px;
                            border-top: 2px solid #e5e7eb;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            font-size: 12px;
                            color: #666;
                        }
                        .footer .generated {
                            font-style: italic;
                        }
                        .signatures {
                            margin-top: 60px;
                            display: flex;
                            justify-content: space-between;
                        }
                        .signature-box {
                            text-align: center;
                        }
                        .signature-line {
                            border-top: 2px solid #333;
                            width: 250px;
                            margin-top: 50px;
                            padding-top: 8px;
                            font-size: 12px;
                            font-weight: 600;
                        }
                        @media print {
                            body {
                                padding: 20px;
                            }
                            .no-print {
                                display: none;
                            }
                        }
                        .print-button {
                            position: fixed;
                            top: 20px;
                            right: 20px;
                            background: #0d9488;
                            color: white;
                            padding: 12px 24px;
                            border: none;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 600;
                            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                        }
                        .print-button:hover {
                            background: #0f766e;
                        }
                    </style>
                </head>
                <body>
                    <button class="print-button no-print" onclick="window.print()">🖨️ Print Report</button>
                    
                    <div class="header">
                        <h1>ATTENDANCE REPORT</h1>
                        <p>Human Resource Information System</p>
                        <p style="margin-top: 10px; font-weight: 600;">${dateStr}</p>
                    </div>

                    <div class="summary">
                        <div class="summary-item">
                            <h3>Total Employees</h3>
                            <p>${visibleRecords.length}</p>
                        </div>
                        <div class="summary-item">
                            <h3>Present</h3>
                            <p style="color: #059669;">${presentCount}</p>
                        </div>
                        <div class="summary-item">
                            <h3>Absent</h3>
                            <p style="color: #dc2626;">${absentCount}</p>
                        </div>
                        <div class="summary-item">
                            <h3>On Leave</h3>
                            <p style="color: #d97706;">${leaveCount}</p>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${visibleRecords.map(record => `
                                <tr>
                                    <td>${record.id}</td>
                                    <td>${record.name}</td>
                                    <td>${record.position}</td>
                                    <td>${record.department}</td>
                                    <td>${record.timeIn}</td>
                                    <td>${record.timeOut}</td>
                                    <td>
                                        <span class="status-badge status-${record.status.toLowerCase()}">
                                            ${record.status}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>

                    <div class="signatures">
                        <div class="signature-box">
                            <div class="signature-line">Prepared by</div>
                        </div>
                        <div class="signature-box">
                            <div class="signature-line">Verified by</div>
                        </div>
                        <div class="signature-box">
                            <div class="signature-line">Approved by</div>
                        </div>
                    </div>

                    <div class="footer">
                        <div class="generated">Generated: ${new Date().toLocaleString()}</div>
                        <div>HRIS - SIA - JRIVERA © ${new Date().getFullYear()}</div>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
        }

        
        document.getElementById('positionFilter').addEventListener('keyup', searchAttendance);
        document.getElementById('departmentFilter').addEventListener('keyup', searchAttendance);