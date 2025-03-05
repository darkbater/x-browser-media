<?php
# Абсолютный путь к записям
$records_dir = __DIR__ . '/records/';
$allowedExtensions = ['mp3', 'wav', 'ogg'];


# Создать каталог, если его нет
if (!is_dir($records_dir)) {
    mkdir($records_dir, 0755, true);
}

# был ли POST-запрос и был ли получен файл
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES) && isset($_FILES['voice'])) {
        $typeFile = explode('/', $_FILES['voice']['type']);
        $uploadFile = $records_dir . basename(md5($_FILES['voice']['tmp_name'].time()).'.'.$typeFile[1]);
        
        if (move_uploaded_file($_FILES['voice']['tmp_name'], $uploadFile)) {
            $response = ['result' => 'OK', 'data' => $uploadFile];
        } else {
            $response = ['result' => 'ERROR', 'data' => ''];
        }
        # JSON-ответ и завершаем выполнение скрипта
        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}


# список файлов
$files = scandir($records_dir);
$files_view = '<div style="padding: 10px;">'
             .'<h2>Аудиофайлы</h2>';
$audioFiles = [];

foreach ($files as $file) {
    $filePath = $records_dir . $file;
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($file[0] === '.' || is_dir($filePath)) continue;
    
    if (in_array($fileExtension, $allowedExtensions)) {
        // Получаем время создания файла
        $timestamp = filemtime($filePath);
        $audioFiles[$timestamp] = $file; // Используем timestamp как ключ
    }
}

// Сортируем по ключам (timestamp) в обратном порядке
krsort($audioFiles);

// Выводим результаты
foreach ($audioFiles as $timestamp => $file) {
    $formattedDate = date('d.m.Y H:i:s', $timestamp);
    $files_view .= '<div style="margin-bottom: 10px; padding: 5px; border: 1px solid #ddd;">'
                 . 'Запись от: ' . htmlspecialchars($formattedDate) . '<br>'
                 . '<audio controls style="width: 100%; height: 1em;">'
                 . '<source src="records/' . htmlspecialchars($file) . '" type="' . mime_content_type($records_dir . $file) . '">'
                 . '</audio>'
                 . '</div>';
}

if (empty($audioFiles)) {
    $files_view .= '<p>Нет доступных записей</p>';
}

$files_view .= '</div>';
# HTML ---------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Recorder</title>
</head>
<body>
    <!-- Добавляем кнопки управления в HTML -->
    <button id="startBtn">Начать запись</button>
    <button id="stopBtn" disabled>Остановить запись</button>
    <?php echo $files_view; ?>
</body>



<script>
// JS -----------------------------------------------------------------------------------
//  доступ к микрофону
console.log('доступа к микрофону...');
navigator.mediaDevices.getUserMedia({ audio: true })
  .then(stream => {
    console.log('Микрофон получен!!');
    
    // let voiceChunks = [];

    // const mediaRecorder = new MediaRecorder(stream);
    
    // const stopBtn = document.getElementById('stopBtn');

    const mediaRecorder = new MediaRecorder(stream);
    let voiceChunks = [];
    const startBtn = document.getElementById('startBtn');
    const stopBtn = document.getElementById('stopBtn');

    // Обработчик сбора аудиоданных
    mediaRecorder.addEventListener("dataavailable", function(event) {
      console.log('получено:', event.data.size, 'байт');
      voiceChunks.push(event.data);
    });

    // Обработчик завершения записи
    mediaRecorder.addEventListener("stop", function() {
      console.log('to stop');
      
      // Создаем аудиофайл vaw
      const voiceBlob = new Blob(voiceChunks, { type: 'audio/wav' });
      console.log('created:', voiceBlob);

      // Формируем данные для отправки
      const formData = new FormData();
      formData.append('voice', voiceBlob, 'recording.wav');
      console.log('form ok');

      // Отправка файла на сервер
      console.log('Начало загрузки ');
      fetch('http://localhost:8099/record', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        console.log('Ответ получен, статус:', response.status);
        return response.json();
      })
      .then(data => {
        console.log('Успешно!:', data);
        // Остановить все треки микрофона
        stream.getTracks().forEach(track => track.stop());
        location.href = '/';
      })
      .catch(error => console.error('Ошибка загрузки:', error));
      
      voiceChunks = []; // Очищаем буфер
    });

    // Управление кнопками
    startBtn.addEventListener('click', () => {
      console.log('REC - > ON !');
      mediaRecorder.start();
      startBtn.disabled = true;
      stopBtn.disabled = false;
      voiceChunks = []; // Очищаем предыдущие данные
    });

    stopBtn.addEventListener('click', () => {
      console.log('REC  - > to STOP');
      if (mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        startBtn.disabled = false;
        stopBtn.disabled = true;
      }
    });

  })
  .catch(error => {
      console.error('Микрофон не дали:', error);
      alert('Микрофон не дали!');
    // Здесь можно добавить обработку ошибки для пользователя
  });

</script>
</html>
