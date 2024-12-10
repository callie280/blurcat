import cv2
import sys
import numpy as np
import os
import logging
from moviepy import VideoFileClip 

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

def apply_blur_logic(frame, detections, blur_option, padding):
    mask_to_blur = np.zeros(frame.shape[:2], dtype=np.uint8)
    faces = []

    for detection in detections[0, 0]:
        confidence = float(detection[2])
        if confidence > 0.5:  # Confidence threshold
            x1 = int(detection[3] * frame.shape[1])
            y1 = int(detection[4] * frame.shape[0])
            x2 = int(detection[5] * frame.shape[1])
            y2 = int(detection[6] * frame.shape[0])
            faces.append((x1, y1, x2 - x1, y2 - y1))

    for i, (x, y, w, h) in enumerate(sorted(faces, key=lambda x: x[0])):
        if blur_option == "first" and i == 0:
            mask_to_blur[max(0, y - padding):min(frame.shape[0], y + h + padding),
                         max(0, x - padding):min(frame.shape[1], x + w + padding)] = 255
        elif blur_option == "second" and i == 1:
            mask_to_blur[max(0, y - padding):min(frame.shape[0], y + h + padding),
                         max(0, x - padding):min(frame.shape[1], x + w + padding)] = 255
        elif blur_option == "3 or more person is blur but first or second are unblurr" and i > 1:
            mask_to_blur[max(0, y - padding):min(frame.shape[0], y + h + padding),
                         max(0, x - padding):min(frame.shape[1], x + w + padding)] = 255
        elif blur_option == "BackgroundPersonOnlyBlur":
            mask_to_blur[:, :] = 255
            for (fx, fy, fw, fh) in faces:
                mask_to_blur[max(0, fy - padding):min(frame.shape[0], fy + fh + padding),
                             max(0, fx - padding):min(frame.shape[1], fx + fw + padding)] = 0
        elif blur_option == "recording":
            mask_to_blur[:, :] = 255
            for (fx, fy, fw, fh) in faces:
                mask_to_blur[max(0, fy - padding):min(frame.shape[0], fy + fh + padding),
                             max(0, fx - padding):min(frame.shape[1], fx + fw + padding)] = 0

    return mask_to_blur


def process_video(input_path, processed_path, blur_option):
    if not os.path.exists(input_path):
        logging.error(f"Input video '{input_path}' does not exist.")
        return False

    cap = cv2.VideoCapture(input_path)
    if not cap.isOpened():
        logging.error("Could not open video file.")
        return False

    model_path = "C:\\xampp\\htdocs\\blurring\\res10_300x300_ssd_iter_140000.caffemodel"
    config_path = "C:\\xampp\\htdocs\\blurring\\deploy.prototxt.txt"
    if not (os.path.exists(model_path) and os.path.exists(config_path)):
        logging.error("DNN model files are missing.")
        return False

    net = cv2.dnn.readNetFromCaffe(config_path, model_path)

    fourcc = cv2.VideoWriter_fourcc(*'vp80')  # WebM codec
    out = cv2.VideoWriter(
        processed_path,
        fourcc,
        int(cap.get(cv2.CAP_PROP_FPS)),
        (int(cap.get(cv2.CAP_PROP_FRAME_WIDTH)), int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT)))
    )

    padding = 15
    frame_count = 0

    while True:
        ret, frame = cap.read()
        if not ret:
            break

        frame_count += 1
        if frame_count % 30 == 0:
            logging.info(f"Processing frame {frame_count}...")

        if blur_option == "none":
            # Directly write the original frame to output
            out.write(frame)
            continue

        try:
            blob = cv2.dnn.blobFromImage(frame, 1.0, (300, 300), [104, 117, 123], False, False)
            net.setInput(blob)
            detections = net.forward()
            mask_to_blur = apply_blur_logic(frame, detections, blur_option, padding)

            blurred_frame = frame.copy()
            blur_area = cv2.GaussianBlur(frame, (35, 35), 30)
            blurred_frame[mask_to_blur == 255] = blur_area[mask_to_blur == 255]
            out.write(blurred_frame)
        except Exception as e:
            logging.error(f"Error processing frame {frame_count}: {e}")

    cap.release()
    out.release()
    logging.info(f"Video processing completed successfully. Processed video saved to '{processed_path}'.")
    return True


def add_audio_to_video(input_video, processed_video):
    # Use moviepy to add the audio from the input video to the processed video
    video_clip = VideoFileClip(processed_video)
    audio_clip = VideoFileClip(input_video).audio

    final_video = video_clip.set_audio(audio_clip)
    final_video.write_videofile(processed_video, codec='libvpx', audio_codec='libvorbis')

    logging.info(f"Audio added to video. Final video saved to '{processed_video}'.")


if __name__ == "__main__":
    if len(sys.argv) < 4:
        logging.error("Usage: python process_video.py <input_video> <processed_video> <blur_option>")
        sys.exit(1)

    input_video = sys.argv[1]
    processed_video = sys.argv[2]
    blur_option = sys.argv[3]

    valid_options = ["first", "second", "3 or more person is blur but first or second are unblurr",
                     "BackgroundPersonOnlyBlur", "recording", "none"]

    if blur_option not in valid_options:
        logging.error(f"Invalid blur option. Choose from {valid_options}")
        sys.exit(1)

    if not process_video(input_video, processed_video, blur_option):
        logging.error("Video processing failed.")
        sys.exit(1)

    # Add audio after processing video
    add_audio_to_video(input_video, processed_video)
