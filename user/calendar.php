<?php
session_start();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Calendar - <?= $fullname ?></title>
<style>
body { font-family: 'Poppins', sans-serif; margin:0; padding:0; background:#f4f6fb; }
header { background: #0d6efd; color:#fff; padding:20px; text-align:center; }
.calendar { max-width:600px; margin:40px auto; background:#fff; padding:20px; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.1);}
.calendar h2 { text-align:center; margin-bottom:20px; }
.calendar table { width:100%; border-collapse:collapse; }
.calendar th, .calendar td { width:14.28%; text-align:center; padding:10px; border-radius:8px; }
.calendar th { background:#0d6efd; color:#fff; }
.calendar td { background:#f0f0f0; margin:2px; cursor:pointer; transition:0.2s; }
.calendar td:hover { background:#0b5ed7; color:#fff; }
.today { background:#0d6efd; color:#fff; font-weight:bold; }
.nav-btn { cursor:pointer; font-weight:bold; padding:5px 10px; border-radius:5px; background:#0d6efd; color:#fff; margin:0 5px; }
.nav-btn:hover { background:#0956c9; }
</style>
</head>
<body>

<header>
  <h1>Calendar - Welcome <?= $fullname ?> 📅</h1>
</header>

<div class="calendar">
  <div style="text-align:center; margin-bottom:15px;">
    <span class="nav-btn" onclick="prevMonth()">&#8592; Prev</span>
    <span id="monthYear"></span>
    <span class="nav-btn" onclick="nextMonth()">Next &#8594;</span>
  </div>
  <table id="calendarTable">
    <thead>
      <tr>
        <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th>
        <th>Thu</th><th>Fri</th><th>Sat</th>
      </tr>
    </thead>
    <tbody>
      <!-- Days will be inserted by JS -->
    </tbody>
  </table>
</div>

<script>
let today = new Date();
let currentMonth = today.getMonth();
let currentYear = today.getFullYear();

const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];

function showCalendar(month, year){
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const tbl = document.querySelector("#calendarTable tbody");
    tbl.innerHTML = "";

    let date = 1;
    for(let i=0; i<6; i++){
        let row = document.createElement("tr");
        for(let j=0; j<7; j++){
            let cell = document.createElement("td");
            if(i===0 && j<firstDay){ cell.innerHTML = ""; }
            else if(date > daysInMonth){ break; }
            else{
                cell.innerHTML = date;
                if(date === today.getDate() && month === today.getMonth() && year === today.getFullYear()){
                    cell.classList.add("today");
                }
                date++;
            }
            row.appendChild(cell);
        }
        tbl.appendChild(row);
    }
    document.getElementById("monthYear").innerText = monthNames[month] + " " + year;
}

function prevMonth(){ currentMonth--; if(currentMonth < 0){ currentMonth=11; currentYear--; } showCalendar(currentMonth, currentYear); }
function nextMonth(){ currentMonth++; if(currentMonth > 11){ currentMonth=0; currentYear++; } showCalendar(currentMonth, currentYear); }

showCalendar(currentMonth, currentYear);
</script>

</body>
</html>
