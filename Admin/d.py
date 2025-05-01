import tkinter as tk
from tkinter import messagebox
import random

# Game logic :)
def create_board():
    return [[" " for _ in range(7)] for _ in range(6)]

def is_valid_location(board, col):
    return board[0][col] == " "

def get_next_open_row(board, col):
    for r in range(5, -1, -1):
        if board[r][col] == " ":
            return r

def drop_piece(board, row, col, piece):
    board[row][col] = piece

def winning_move(board, piece):
    for c in range(4):
        for r in range(6):
            if board[r][c] == board[r][c+1] == board[r][c+2] == board[r][c+3] == piece:
                return True
    for c in range(7):
        for r in range(3):
            if board[r][c] == board[r+1][c] == board[r+2][c] == board[r+3][c] == piece:
                return True
    for c in range(4):
        for r in range(3):
            if board[r][c] == board[r+1][c+1] == board[r+2][c+2] == board[r+3][c+3] == piece:
                return True
    for c in range(4):
        for r in range(3, 6):
            if board[r][c] == board[r-1][c+1] == board[r-2][c+2] == board[r-3][c+3] == piece:
                return True
    return False

def get_fuzzy_ai_move(board):
    scores = {}
    for col in range(7):
        if is_valid_location(board, col):
            # استراتيجيات متعددة لتحسين حركات الذكاء الاصطناعي:
            center_bonus = 3 - abs(3 - col)
            random_bonus = random.randint(0, 2)
            scores[col] = center_bonus + random_bonus
    best_col = max(scores, key=scores.get)
    return best_col

# GUI part
class Connect4App:
    def _init_(self, root):
        self.root = root
        self.root.title("Connect 4")
        self.board = create_board()
        self.turn = 0
        self.game_over = False
        self.mode = None
        self.cell_size = 80

        self.start_screen()

    def start_screen(self):
        self.frame = tk.Frame(self.root, bg="lightblue")
        self.frame.pack(fill="both", expand=True)

        label = tk.Label(self.frame, text="Select Mode", font=("Arial", 20), bg="lightblue")
        label.pack(pady=20)

        btn1 = tk.Button(self.frame, text="Human vs Human", command=lambda: self.start_game("1"), width=20, height=2, bg="#4CAF50", fg="white", font=("Arial", 12, "bold"))
        btn1.pack(pady=10)

        btn2 = tk.Button(self.frame, text="Human vs AI", command=lambda: self.start_game("2"), width=20, height=2, bg="#f44336", fg="white", font=("Arial", 12, "bold"))
        btn2.pack(pady=10)

        btn3 = tk.Button(self.frame, text="AI vs AI", command=lambda: self.start_game("3"), width=20, height=2, bg="#FF9800", fg="white", font=("Arial", 12, "bold"))
        btn3.pack(pady=10)

    def start_game(self, mode):
        self.mode = mode
        self.frame.destroy()

        self.top_frame = tk.Frame(self.root)
        self.top_frame.pack()
        self.status_label = tk.Label(self.top_frame, text="Player 1 Turn", font=("Arial", 16))
        self.status_label.pack(pady=10)

        self.canvas = tk.Canvas(self.root, width=7*self.cell_size, height=6*self.cell_size, bg='blue', bd=5, relief="ridge")
        self.canvas.pack()
        self.canvas.bind("<Button-1>", self.click_event)

        self.restart_btn = tk.Button(self.root, text="Restart", command=self.restart_game, width=10, bg="#2196F3", fg="white", font=("Arial", 12))
        self.restart_btn.pack(pady=10)

        self.draw_board()

        if self.mode == "3":
            # Start AI vs AI automatically
            self.ai_vs_ai_turn()

    def draw_board(self):
        self.canvas.delete("all")
        for r in range(6):
            for c in range(7):
                x0 = c * self.cell_size + 5
                y0 = r * self.cell_size + 5
                x1 = x0 + self.cell_size - 10
                y1 = y0 + self.cell_size - 10
                color = "white"
                if self.board[r][c] == "X":
                    color = "red"
                elif self.board[r][c] == "O":
                    color = "yellow"
                self.canvas.create_oval(x0, y0, x1, y1, fill=color, outline="black", width=2)

    def click_event(self, event):
        if self.game_over or self.mode == "3":
            return

        col = event.x // self.cell_size
        if is_valid_location(self.board, col):
            self.play_turn(col)

    def play_turn(self, col):
        if is_valid_location(self.board, col):
            row = get_next_open_row(self.board, col)
            piece = "X" if self.turn == 0 else "O"
            player = "Player 1" if self.turn == 0 else ("Player 2" if self.mode == "1" else "AI")

            drop_piece(self.board, row, col, piece)
            self.draw_board()

            if winning_move(self.board, piece):
                self.game_over = True
                if self.mode == "1":
                    messagebox.showinfo("Game Over", f"{player} wins!")
                else:
                    if player == "Player 1":
                        messagebox.showinfo("Game Over", "You Win!")
                    else:
                        messagebox.showinfo("Game Over", "You Lose!")
                return

            self.turn = (self.turn + 1) % 2
            self.update_status()

            if self.mode == "2" and self.turn == 1 and not self.game_over:
                self.root.after(500, self.ai_turn)

    def ai_turn(self):
        col = get_fuzzy_ai_move(self.board)
        self.play_turn(col)

    def ai_vs_ai_turn(self):
        # AI's turn without any player intervention
        if not self.game_over:
            col = get_fuzzy_ai_move(self.board)
            self.play_turn(col)
            # Delay next move
            self.root.after(500, self.ai_vs_ai_turn)

    def update_status(self):
        if self.mode == "1":
            text = "Player 1 Turn" if self.turn == 0 else "Player 2 Turn"
        else:
            text = "Your Turn" if self.turn == 0 else "AI Turn"
        self.status_label.config(text=text)

    def restart_game(self):
        self.board = create_board()
        self.turn = 0
        self.game_over = False
        self.update_status()
        self.draw_board()

if __name__ == "_main_":
    root = tk.Tk()
    app = Connect4App(root)
    root.mainloop()