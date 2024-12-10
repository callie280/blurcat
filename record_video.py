from flask import Flask, request, jsonify
import cv2
import numpy as np

app = Flask(__name__)

# Global variable to store blur value
blur_value = 0

@app.route('/update-blur', methods=['POST'])
def update_blur():
    global blur_value
    data = request.get_json()
    blur_value = int(data['blur'])  # Update global blur value
    return jsonify({"status": "success", "blur_value": blur_value})

def apply_blur(frame, blur_value):
    """Apply Gaussian blur to a frame."""
    if blur_value > 0:
        return cv2.GaussianBlur(frame, (15, 15), blur_value)
    return frame

def record_video():
    cap = cv2.VideoCapture(0)  # Open webcam (0 for default camera)
    
    while True:
        ret, frame = cap.read()
        if not ret:
            break
        
        # Apply the current blur value to the frame
        frame = apply_blur(frame, blur_value)

        # Display the frame (with or without blur)
        cv2.imshow('Video Stream', frame)

        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    cap.release()
    cv2.destroyAllWindows()
    

if __name__ == '__main__':
    # Start Flask server to receive blur updates
    from threading import Thread
    flask_thread = Thread(target=lambda: app.run(debug=True, use_reloader=False))
    flask_thread.start()

    # Start recording video
    record_video()
