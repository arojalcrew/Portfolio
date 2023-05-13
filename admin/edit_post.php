<?php

    @include '../components/connect.php';

    session_start();

    $admin_id = $_SESSION['admin_id'];

    if(!isset($admin_id))
    {
        header('location:admin_login.php');
    }

    if(!isset($_GET['post_id']))
    {
        header('location:view_posts.php');
    }
    else
    {
        $get_id = $_GET['post_id'];
    }

    if(isset($_POST['save']))
    {
        $title = $_POST['title'];
        $title = filter_var($title, FILTER_SANITIZE_STRING);
        $content = $_POST['content'];
        $content = filter_var($content, FILTER_SANITIZE_STRING);
        $category = $_POST['category'];
        $category = filter_var($category, FILTER_SANITIZE_STRING);
        $status = $_POST['status'];
        $status = filter_var($status, FILTER_SANITIZE_STRING);

        $update_post = $conn->prepare("UPDATE `posty` SET title = ?, content = ?, 
        category = ?, status = ? WHERE id = ?");
        $update_post->execute([$title, $content, $category, $status, $get_id]);

        $message[] = "Post zaktualizowany.";

        $old_image = $_POST['old_image'];
        $old_image = filter_var($old_image, FILTER_SANITIZE_STRING);
        $image = $_FILES['image']['name'];
        $image = filter_var($image, FILTER_SANITIZE_STRING);
        $image_size = $_FILES['image']['size'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = '../uploaded_img/'.$image;

        $select_image = $conn->prepare("SELECT * FROM `posty` WHERE image = ? AND admin_id = ?");
        $select_image->execute([$image, $admin_id]);

        if(!empty($image))
        {
            if($select_image->rowCount() > 0 AND $image != '')
            {
                $message[] = 'Nazwa zdjęcia się powtarza';
            }
            elseif ($image_size > 200000000)
            {
                $message[] = 'Zdjęcie waży za dużo';
            }
            else
            {
                $update_image = $conn->prepare("UPDATE `posty` SET image = ? WHERE id = ?");
                $update_image->execute([$image, $get_id]);
                move_uploaded_file($image_tmp_name, $image_folder);
                $message[] = "Zdjęcie zaktualizowane.";
                if($old_image != $image AND $old_image != '')
                {
                    unlink('../uploaded_img/'.$old_image);
                }
            }
        }
        else
        {
            $image = '';
        }

    }

    if(isset($_POST['delete']))
    {
        $delete_id = $_POST['post_id'];
        $delete_id = filter_var($delete_id, FILTER_SANITIZE_STRING);
        $select_image = $conn->prepare("SELECT * FROM `posty` WHERE id = ?");
        $select_image->execute([$delete_id]);
        $fetch_image = $select_image->fetch(PDO::FETCH_ASSOC);
        if($fetch_image['image'] != '')
        {
            unlink('../uploaded_img/'.$fetch_image['image']);
        }
        $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE post_id = ?");
        $delete_comments->execute([$delete_id]);

        $delete_likes = $conn->prepare("DELETE FROM `likes` WHERE post_id = ?");
        $delete_likes->execute([$delete_id]);

        $delete_post = $conn->prepare("DELETE FROM `posty` WHERE id = ?");
        $delete_post->execute([$delete_id]);
        header('location:view_posts.php');
    }

    if(isset($_POST['delete_image']))
    {
        $empty_image = '';
        $select_image = $conn->prepare("SELECT * FROM `posty` WHERE id = ?");
        $select_image->execute([$get_id]);
        $fetch_image = $select_image->fetch(PDO::FETCH_ASSOC);
        if($fetch_image['image'] != '')
        {
            unlink('../uploaded_img/'.$fetch_image['image']);
        }
        $unset_image = $conn->prepare("UPDATE `posty` SET image = ? WHERE id = ?");
        $unset_image->execute([$empty_image, $get_id]);
        $message[] = 'Zdjęcie usunięte';
    }

    ?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/admin_style.css">
    <title>Edytuj post</title>
</head>
<body>
    
    <?php include '../components/admin_header.php'; ?>

    <section class="post-editor">
        <h1 class="heading">Edytuj post</h1>

        <?php

                $select_posts = $conn->prepare("SELECT * FROM `posty` WHERE id = ? AND admin_id = ?");
                $select_posts->execute([$get_id, $admin_id]);
                if($select_posts->rowCount() > 0)
                {
                    while($fetch_post = $select_posts->fetch(PDO::FETCH_ASSOC))
                    {   
                
            ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="post_id" value="<?= $fetch_post['id'] ?>">
            <input type="hidden" name="old_image" value="<?= $fetch_post['image'] ?>">
            <input type="hidden" name="name" value="<?= $fetch_profile['name']; ?>">
            <p>Status posta <span>*</span></p>
            <select name="status" required class="box">
                <option value="<?= $fetch_post['status'] ?>" selected><?= $fetch_post['status'] ?></option>
                <option value="Aktywny">Aktywny</option>
                <option value="Nieaktywny">Nieaktywny</option>
            </select>
            <p>Tytuł posta <span>*</span></p>
            <input type="text" maxlength="255" name="title" required placeholder="Dodaj tytuł posta" class="box" value="<?= $fetch_post['title']; ?>">
            <p>Zawartosć posta<span>*</span></p>
            <textarea name="content" class="box" required maxlength="10000" placeholder="Wpisz zawartosć..." cols="30" rows="10"><?= $fetch_post['content']; ?></textarea>
            <p>Kategoria posta <span>*</span></p>
            <select name="category" class="box" required>
                <option value="<?= $fetch_post['category']; ?>" selected><?= $fetch_post['category']; ?></option>
                <option value="Harmonogramy">Harmonogramy</option>
                <option value="Aktualności">Aktualności</option>
                <option value="Przetargi">Przetargi</option>
            </select>
            <p>Zdjęcie posta</p>
            <input type="file" name="image" accept="image/jpg, image/jpeg, image/png, image/webp" class="box">
            <?php 
                if($fetch_post['image'] != '')
                {

            ?>
            <img src="../uploaded_img/<?= $fetch_post['image']; ?>" alt="" class="image">
            <input type="submit" value="Usuń zdjęcie" name="delete_image" class="inline-delete-btn">
            <?php 
                    }
            ?>
            <div class="flex-btn">
                <input type="submit" value="Zapisz post" name="save" class="btn">
                <a href="view_posts.php" class="option-btn">Wróć</a>
                <button type="submit" name="delete" onclick="return confirm('Usunąć ten post?');" class="delete-btn">Usuń</button>
            </div>
        </form>
        <?php
                }
            } 
                else
                {
                    echo '<p class="empty">Brak dodanych postów</p>';
                }

            ?>
    </section>








    <script src="../js/admin_script2.js"></script>
</body>
</html>